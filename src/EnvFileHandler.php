<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager;

use Dotenv\Dotenv;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Mumincacao\LaravelEnvManager\Contracts\CommandProxy;
use Throwable;

class EnvFileHandler
{
    private readonly string $envFilePath;

    private readonly FilesystemOperator $filesystem;

    private readonly string $plainEnvFile;

    private readonly string $encryptedEnvFile;

    private readonly string $exampleEnvFile;

    public function __construct(
        private readonly string $basePath,
        private readonly string $env,
        private readonly ?string $key,
        private readonly CommandProxy $command,
        ?FilesystemOperator $filesystem = null,
    ) {
        $this->envFilePath = "{$this->basePath}/.env.{$this->env}";
        $this->plainEnvFile = ".env.{$this->env}";
        $this->encryptedEnvFile = ".env.{$this->env}.encrypted";
        $this->exampleEnvFile = '.env.example';
        $this->filesystem = $filesystem ?? new Filesystem(new LocalFilesystemAdapter($this->basePath));
    }

    public function load(): EnvRepository
    {
        $this->setupEnvFile();

        $data = Dotenv::createMutable(dirname($this->envFilePath), basename($this->envFilePath))->load();

        return new EnvRepository($data);
    }

    public function save(EnvRepository $repository, bool $clean = false): void
    {
        $content = [];
        foreach ($repository->all() as $key => $value) {
            $content[] = sprintf('%s="%s"', $key, $value);
        }

        try {
            $this->filesystem->write($this->plainEnvFile, implode(PHP_EOL, $content));
        } catch (Throwable) {
            $this->command->fail("Failed to write to .env file for environment '{$this->env}'.");
        }

        $encOptions = $this->makeEncryptionOptions($clean);
        if ($this->command->call('env:encrypt', $encOptions) !== 0) {
            $this->command->fail("Failed to encrypt .env file for environment '{$this->env}'.");
        }
    }

    private function setupEnvFile(): void
    {
        // Priority: .env.{env}.encrypted > .env.{env} > .env.example
        match (true) {
            $this->filesystem->fileExists($this->encryptedEnvFile) => $this->setupByEncryptedFile(),
            $this->filesystem->fileExists($this->plainEnvFile) => $this->setupByPlainFile(),
            default => $this->setupByExampleFile(),
        };
    }

    private function setupByEncryptedFile(): void
    {
        $options = ['--env' => $this->env, '--key' => $this->key, '--force' => true];
        if ($this->command->call('env:decrypt', $options) !== 0) {
            $this->command->fail("Failed to decrypt .env.{$this->env}.encrypted");
        }
    }

    private function setupByPlainFile(): void
    {
        $this->command->info("Using existing .env file for environment '{$this->env}'.");
    }

    private function setupByExampleFile(): void
    {
        if ($this->filesystem->fileExists($this->exampleEnvFile) === false) {
            $this->command->fail("No .env.{$this->env} and .env.example file found.");
        }

        try {
            $this->filesystem->copy($this->exampleEnvFile, $this->plainEnvFile);
        } catch (Throwable) {
            $this->command->fail("Failed to create .env file for environment '{$this->env}' from .env.example.");
        }

        $this->command->info("Created .env file for environment '{$this->env}' from .env.example.");
    }

    /**
     * @return array{'--env': string, '--key': string|null, '--force': true, '--readable': true, '--prune': bool}
     */
    private function makeEncryptionOptions(bool $clean): array
    {
        return [
            '--env' => $this->env,
            '--key' => $this->key,
            '--force' => true,
            '--readable' => true,
            '--prune' => $clean,
        ];
    }
}
