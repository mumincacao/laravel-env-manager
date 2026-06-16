<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\ChangesAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ChangesAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionChangesTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('List only changed environment variables', ChangesAction::class);
    }

    public function testExecute(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1', 'VAR2' => 'value2', 'VAR3' => 'value3']);
        $this->repository->set('VAR4', 'value4');
        $this->repository->remove('VAR2');
        $this->repository->set('VAR3', 'value3_modified');

        $isFinish = $handler->handle('changes');

        $this->assertFalse($isFinish);
        $this->assertContains('Changed environment variables:', $this->getMessages('info'));
        $this->assertContains('- VAR2=value2', $this->getMessages('error'));
        $this->assertContains('- VAR3=value3', $this->getMessages('error'));
        $this->assertContains('+ VAR3=value3_modified', $this->getMessages('info'));
        $this->assertContains('+ VAR4=value4', $this->getMessages('info'));
    }

    public function testEmptyRepository(): void
    {
        $handler = $this->createHandler();
        $isFinish = $handler->handle('changes');

        $this->assertFalse($isFinish);
        $this->assertContains('No environment variables found.', $this->getMessages('info'));
    }

    public function testNoChanges(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1']);
        $isFinish = $handler->handle('changes');

        $this->assertFalse($isFinish);
        $this->assertContains('Changed environment variables:', $this->getMessages('info'));
        $this->assertContains('No changes detected.', $this->getMessages('info'));
    }
}
