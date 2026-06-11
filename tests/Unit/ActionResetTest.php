<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\ResetAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ResetAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionResetTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('Reset all changes to the original environment variables', ResetAction::class);
    }

    public function testExecute(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1', 'VAR2' => 'value2']);
        $this->repository->set('VAR3', 'value3');
        $this->repository->remove('VAR1');
        $this->repository->set('VAR2', 'value2_modified');
        $this->setConfirmationResponse(true);

        $isFinish = $handler->handle('reset');

        $this->assertFalse($isFinish);
        $this->assertTrue($this->repository->has('VAR1'));
        $this->assertSame('value1', $this->repository->get('VAR1'));
        $this->assertTrue($this->repository->has('VAR2'));
        $this->assertSame('value2', $this->repository->get('VAR2'));
        $this->assertFalse($this->repository->has('VAR3'));
    }

    public function testCancel(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1']);
        $this->repository->set('VAR2', 'value2');
        $this->repository->remove('VAR1');
        $this->setConfirmationResponse(false);

        $isFinish = $handler->handle('reset');

        $this->assertFalse($isFinish);
        $this->assertFalse($this->repository->has('VAR1'));
        $this->assertTrue($this->repository->has('VAR2'));
    }
}
