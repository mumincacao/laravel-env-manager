<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\FinishAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(FinishAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionFinishTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('Apply all changes to the .env file', FinishAction::class);
    }

    public function testExecute(): void
    {
        $handler = $this->createHandler(['VAR1' => 'value1', 'VAR2' => 'value2']);
        $isFinish = $handler->handle('finish');

        // Since FinishAction does not modify the repository, we just check that the variables are still there
        $this->assertTrue($isFinish);
        $this->assertTrue($this->repository->has('VAR1'));
        $this->assertTrue($this->repository->has('VAR2'));
    }
}
