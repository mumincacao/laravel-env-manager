<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\GrepAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(GrepAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionGrepTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('Search for environment variables', GrepAction::class);
    }

    public function testExecute(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1', 'VAR2' => 'value2', 'VAR3' => 'value3']);
        $this->setAskResponse('VAR');

        $this->repository->set('VAR4', 'value4');
        $this->repository->remove('VAR2');
        $this->repository->set('VAR3', 'value3_modified');

        $isFinish = $handler->handle('grep');

        $this->assertFalse($isFinish);
        $this->assertContains('Matching environment variables:', $this->getMessages('info'));
        $this->assertContains('- VAR2=value2', $this->getMessages('error'));
        $this->assertContains('- VAR3=value3', $this->getMessages('error'));
        $this->assertContains('+ VAR3=value3_modified', $this->getMessages('info'));
        $this->assertContains('+ VAR4=value4', $this->getMessages('info'));
    }

    public function testEmptyRepository(): void
    {
        $handler = $this->createHandler();
        $isFinish = $handler->handle('grep');

        $this->assertFalse($isFinish);
        $this->assertContains('No environment variables found.', $this->getMessages('info'));
    }

    public function testNoMatches(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1', 'VAR2' => 'value2']);
        $this->setAskResponse('NON_EXISTENT');

        $isFinish = $handler->handle('grep');

        $this->assertFalse($isFinish);
        $this->assertContains('No matching environment variables found.', $this->getMessages('info'));
    }

    public function testEmptyKeyword(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1']);
        $this->setAskResponse('');
        $this->setAskResponse('VAR'); // Provide a valid keyword after the first empty input

        $isFinish = $handler->handle('grep');

        $this->assertFalse($isFinish);
        $this->assertContains('Search word cannot be empty. Please enter again.', $this->getMessages('error'));
        $this->assertContains('Matching environment variables:', $this->getMessages('info'));
        $this->assertContains('  VAR1=value1', $this->getMessages('line'));
    }

    public function testNullKeyword(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1']);
        $this->setAskResponse(null);
        $this->setAskResponse('VAR'); // Provide a valid keyword after the first null input

        $isFinish = $handler->handle('grep');

        $this->assertFalse($isFinish);
        $this->assertContains('Search word cannot be empty. Please enter again.', $this->getMessages('error'));
        $this->assertContains('Matching environment variables:', $this->getMessages('info'));
        $this->assertContains('  VAR1=value1', $this->getMessages('line'));
    }

    public static function searchTypeProvider(): array
    {
        return [
            'key keep' => ['VAR1', 'line', '  VAR1=value1'],
            'key add' => ['VAR3', 'info', '+ VAR3=another value'],
            'key remove' => ['VAR2', 'error', '- VAR2=value2'],

            'value keep' => ['value1', 'line', '  VAR1=value1'],
            'value add' => ['another value', 'info', '+ VAR3=another value'],
            'value remove' => ['value2', 'error', '- VAR2=value2'],

            'original value keep' => ['value1', 'line', '  VAR1=value1'],
            'original value remove' => ['value2', 'error', '- VAR2=value2'],
        ];
    }

    #[DataProvider('searchTypeProvider')]
    public function testSearchTypes(string $keyword, string $type, string $output): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1', 'VAR2' => 'value2']);
        $this->repository->set('VAR3', 'another value');
        $this->repository->remove('VAR2');
        $this->setAskResponse($keyword);

        $isFinish = $handler->handle('grep');

        $this->assertFalse($isFinish);
        $this->assertContains($output, $this->getMessages($type));
    }
}
