<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\ListAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ListAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionListTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('List all environment variables and their statuses', ListAction::class);
    }

    public function testExecute(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1', 'VAR2' => 'value2', 'VAR3' => 'value3']);
        $this->repository->set('VAR4', 'value4');
        $this->repository->remove('VAR2');
        $this->repository->set('VAR3', 'value3_modified');

        $isFinish = $handler->handle('list');

        $this->assertFalse($isFinish);
        $this->assertContains('Current environment variables:', $this->getMessages('info'));
        $this->assertContains('-   VAR1=value1', $this->getMessages('line'));
        $this->assertContains('- D VAR2= (original: value2)', $this->getMessages('error'));
        $this->assertContains('- M VAR3=value3_modified (original: value3)', $this->getMessages('warn'));
        $this->assertContains('- A VAR4=value4', $this->getMessages('info'));
    }

    public function testEmptyRepository(): void
    {
        $handler = $this->createHandler();
        $isFinish = $handler->handle('list');

        $this->assertFalse($isFinish);
        $this->assertContains('No environment variables found.', $this->getMessages('info'));
    }
}
