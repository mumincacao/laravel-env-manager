<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use League\Flysystem\FilesystemOperator;
use Mumincacao\LaravelEnvManager\EnvFileHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\Mocks\MockCommand;
use Mumincacao\LaravelEnvManager\tests\Mocks\MockFailException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(EnvFileHandler::class)]
#[UsesClass(EnvRepository::class)]
class EnvFileHandlerTest extends TestCase
{
    private const BASE_PATH = __DIR__ . '/../stubs/env';

    private const PLAIN_ENV_SUFFIX = 'demo';

    private const PLAIN_ENV_PATH = self::BASE_PATH . '/.env.' . self::PLAIN_ENV_SUFFIX;

    private const ENCRYPTED_ENV_SUFFIX = 'demo.encrypted';

    private const ENCRYPTED_ENV_PATH = self::BASE_PATH . '/.env.' . self::ENCRYPTED_ENV_SUFFIX;

    private const EXAMPLE_ENV_SUFFIX = 'example';

    /**
     * Plain: none, Encrypted: none, Example: none -> Fail
     */
    public function testEnvFileNotFound(): void
    {
        $command = new MockCommand();
        $handler = $this->makeHandler($command);

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage($this->makeMessage('No .env.{env} and .env.{example_env} file found.'));

        $handler->load();
    }

    /**
     * Plain: none, Encrypted: none, Example: exists -> Copy from example
     */
    public function testEnvFileExampleOnly(): void
    {
        $this->makeEnvFile(self::EXAMPLE_ENV_SUFFIX, ['APP_NAME' => 'ExampleApp']);

        $command = new MockCommand();
        $handler = $this->makeHandler($command);

        $this->assertFileDoesNotExist(self::PLAIN_ENV_PATH);

        $repository = $handler->load();

        $this->assertSame('ExampleApp', $repository->get('APP_NAME'));
        $this->assertFileExists(self::PLAIN_ENV_PATH);
        $this->assertContains(
            $this->makeMessage('Created .env file for environment \'{env}\' from .env.{example_env}.'),
            $command->getMessages('info')
        );
    }

    /**
     * Plain: none, Encrypted: exists, Example: none -> Decrypt and load from encrypted file
     */
    public function testEnvFileEncryptedOnly(): void
    {
        $this->makeEnvFile(self::ENCRYPTED_ENV_SUFFIX, ['APP_NAME' => 'EncryptedApp']);

        $command = new MockCommand();
        $command->setCallResponse($this->decryptCallback(...));
        $handler = $this->makeHandler($command);

        $this->assertFileDoesNotExist(self::PLAIN_ENV_PATH);

        $repository = $handler->load();

        $this->assertSame('EncryptedApp', $repository->get('APP_NAME'));
        $this->assertFileExists(self::PLAIN_ENV_PATH);
        $this->assertEmpty($command->getMessages('info'));
    }

    /**
     * Plain: none, Encrypted: exists, Example: exists -> Decrypt and load from encrypted file (ignore example)
     */
    public function testEnvFileEncryptedOverridesExample(): void
    {
        $this->makeEnvFile(self::ENCRYPTED_ENV_SUFFIX, ['APP_NAME' => 'EncryptedApp']);
        $this->makeEnvFile(self::EXAMPLE_ENV_SUFFIX, ['APP_NAME' => 'ExampleApp']);

        $command = new MockCommand();
        $command->setCallResponse($this->decryptCallback(...));
        $handler = $this->makeHandler($command);

        $this->assertFileDoesNotExist(self::PLAIN_ENV_PATH);

        $repository = $handler->load();

        $this->assertSame('EncryptedApp', $repository->get('APP_NAME'));
        $this->assertFileExists(self::PLAIN_ENV_PATH);
        $this->assertEmpty($command->getMessages('info'));
    }

    /**
     * Plain: exists, Encrypted: none, Example: none -> Load from plain file
     */
    public function testEnvFilePlainOnly(): void
    {
        $this->makeEnvFile(self::PLAIN_ENV_SUFFIX, ['APP_NAME' => 'PlainApp']);

        $command = new MockCommand();
        $handler = $this->makeHandler($command);

        $repository = $handler->load();

        $this->assertSame('PlainApp', $repository->get('APP_NAME'));
        $this->assertFileExists(self::PLAIN_ENV_PATH);
        $this->assertContains(
            $this->makeMessage('Using existing .env file for environment \'{env}\'.'),
            $command->getMessages('info')
        );
    }

    /**
     * Plain: exists, Encrypted: none, Example: exists -> Load from plain file (ignore example)
     */
    public function testEnvFilePlainOverridesExample(): void
    {
        $this->makeEnvFile(self::PLAIN_ENV_SUFFIX, ['APP_NAME' => 'PlainApp']);
        $this->makeEnvFile(self::EXAMPLE_ENV_SUFFIX, ['APP_NAME' => 'ExampleApp']);

        $command = new MockCommand();
        $handler = $this->makeHandler($command);

        $repository = $handler->load();

        $this->assertSame('PlainApp', $repository->get('APP_NAME'));
        $this->assertFileExists(self::PLAIN_ENV_PATH);
        $this->assertContains(
            $this->makeMessage('Using existing .env file for environment \'{env}\'.'),
            $command->getMessages('info')
        );
    }

    /**
     * Plain: exists, Encrypted: exists, Example: exists -> Decrypt and load from encrypted file (overrides plain file)
     */
    public function testEnvFileEncryptedOverridesPlain(): void
    {
        $this->makeEnvFile(self::PLAIN_ENV_SUFFIX, ['APP_NAME' => 'PlainApp']);
        $this->makeEnvFile(self::ENCRYPTED_ENV_SUFFIX, ['APP_NAME' => 'EncryptedApp']);
        $this->makeEnvFile(self::EXAMPLE_ENV_SUFFIX, ['APP_NAME' => 'ExampleApp']);

        $command = new MockCommand();
        $command->setCallResponse($this->decryptCallback(...));
        $handler = $this->makeHandler($command);

        $repository = $handler->load();

        $this->assertSame('EncryptedApp', $repository->get('APP_NAME'));
        $this->assertFileExists(self::PLAIN_ENV_PATH);
        $this->assertEmpty($command->getMessages('info'));
        $this->assertSame('APP_NAME=EncryptedApp', file_get_contents(self::PLAIN_ENV_PATH));
    }

    public function testEnvDecryptFailure(): void
    {
        $this->makeEnvFile(self::ENCRYPTED_ENV_SUFFIX, ['APP_NAME' => 'EncryptedApp']);

        $command = new MockCommand();
        $command->setCallResponse(1); // Simulate decryption failure
        $handler = $this->makeHandler($command);

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage($this->makeMessage('Failed to decrypt .env.{env}.encrypted'));

        $handler->load();
    }

    public function testEnvFileWriteFailure(): void
    {
        $this->makeEnvFile(self::EXAMPLE_ENV_SUFFIX, ['APP_NAME' => 'ExampleApp']);

        $command = new MockCommand();
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('fileExists')->willReturnMap([
            ['.env.demo.encrypted', false],
            ['.env.demo', false],
            ['.env.example', true],
        ]);
        $filesystem->expects($this->once())
            ->method('copy')
            ->with('.env.example', '.env.demo')
            ->willThrowException(new RuntimeException('Unable to copy file.'));
        $handler = $this->makeHandler($command, filesystem: $filesystem);

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage(
            $this->makeMessage('Failed to create .env file for environment \'{env}\' from .env.example.')
        );

        $handler->load();
    }

    public function testSaveWithoutClean(): void
    {
        $command = new MockCommand();
        $command->setCallResponse($this->encryptCallback(...));
        $handler = $this->makeHandler($command);

        $repository = new EnvRepository(['APP_NAME' => 'SavedApp']);
        $handler->save($repository, clean: false);

        $this->assertFileExists(self::PLAIN_ENV_PATH);
        $this->assertSame('APP_NAME="SavedApp"', file_get_contents(self::PLAIN_ENV_PATH));
        $this->assertFileExists(self::ENCRYPTED_ENV_PATH);
        $this->assertSame('APP_NAME="SavedApp"', file_get_contents(self::ENCRYPTED_ENV_PATH));
    }

    public function testSaveWithClean(): void
    {
        $command = new MockCommand();
        $command->setCallResponse($this->encryptCallback(...));
        $handler = $this->makeHandler($command);

        $repository = new EnvRepository(['APP_NAME' => 'SavedApp']);
        $handler->save($repository, clean: true);

        $this->assertFileDoesNotExist(self::PLAIN_ENV_PATH);
        $this->assertFileExists(self::ENCRYPTED_ENV_PATH);
        $this->assertSame('APP_NAME="SavedApp"', file_get_contents(self::ENCRYPTED_ENV_PATH));
    }

    public function testSavePlainFailure(): void
    {
        $command = new MockCommand();
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())
            ->method('write')
            ->with('.env.demo', 'APP_NAME="SavedApp"')
            ->willThrowException(new RuntimeException('Unable to write file.'));
        $handler = $this->makeHandler($command, filesystem: $filesystem);

        $repository = new EnvRepository(['APP_NAME' => 'SavedApp']);

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage(
            $this->makeMessage('Failed to write to .env file for environment \'{env}\'.')
        );

        $handler->save($repository);
    }

    public function testSaveEncryptFailure(): void
    {
        $command = new MockCommand();
        $command->setCallResponse(1); // Simulate encryption failure
        $handler = $this->makeHandler($command);

        $repository = new EnvRepository(['APP_NAME' => 'SavedApp']);

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage(
            $this->makeMessage('Failed to encrypt .env file for environment \'{env}\'.')
        );

        $handler->save($repository);
    }

    public function testKeepEncKey(): void
    {
        $key = 'secretkey';
        $this->makeEnvFile(self::ENCRYPTED_ENV_SUFFIX, ['APP_NAME' => 'EncryptedApp']);

        $command = new MockCommand();
        $command->setCallResponse(function (string $command, array $arguments) use ($key) {
            $this->assertSame($key, $arguments['--key'] ?? null);
            if ($command === 'env:decrypt') {
                $this->decryptCallback($command, $arguments);
            }

            return 0;
        });
        $handler = $this->makeHandler($command, $key);

        $repository = $handler->load();
        $handler->save($repository);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanupEnvFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupEnvFiles();

        parent::tearDown();
    }

    private function cleanupEnvFiles(): void
    {
        $files = glob(self::BASE_PATH . '/.env.*');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function makeHandler(
        MockCommand $command,
        string $key = '',
        ?FilesystemOperator $filesystem = null,
    ): EnvFileHandler
    {
        return new EnvFileHandler(self::BASE_PATH, 'demo', $key, $command, $filesystem);
    }

    /**
     * @param  array<string, string> $data
     */
    private function makeEnvFile(string $env, array $data): void
    {
        $content = [];
        foreach ($data as $key => $value) {
            $content[] = "{$key}={$value}";
        }

        file_put_contents(self::BASE_PATH . "/.env.{$env}", implode(PHP_EOL, $content));
    }

    private function decryptCallback(string $command, array $arguments): int
    {
        copy(self::ENCRYPTED_ENV_PATH, self::PLAIN_ENV_PATH);

        return 0;
    }

    private function encryptCallback(string $command, array $arguments): int
    {
        copy(self::PLAIN_ENV_PATH, self::ENCRYPTED_ENV_PATH);
        if ($arguments['--prune'] ?? false) {
            unlink(self::PLAIN_ENV_PATH);
        }

        return 0;
    }

    /**
     * @param  string $message  The expected message with placeholders like {env} and {example_env}
     */
    private function makeMessage(string $message): string
    {
        return strtr($message, [
            '{env}' => self::PLAIN_ENV_SUFFIX,
            '{example_env}' => self::EXAMPLE_ENV_SUFFIX,
        ]);
    }
}
