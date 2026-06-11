<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\DeleteAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(DeleteAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionDeleteTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('Delete an environment variable', DeleteAction::class);
    }

    public function testDeleteExistingVariable(): void
    {
        $handler = $this->createHandler(['EXISTING_VAR' => 'value']);
        $this->setAnticipateResponse('EXISTING_VAR');

        $isFinish = $handler->handle('delete');

        $this->assertFalse($isFinish);
        $this->assertFalse($this->repository->has('EXISTING_VAR'));
        $this->assertContains("Variable 'EXISTING_VAR' has been deleted.", $this->getMessages('info'));
    }

    public function testDeleteNonExistingVariable(): void
    {
        $handler = $this->createHandler(['EXISTING_VAR' => 'value']);
        $this->setAnticipateResponse('NON_EXISTING_VAR');
        $isFinish = $handler->handle('delete');

        $this->assertFalse($isFinish);
        $this->assertTrue($this->repository->has('EXISTING_VAR'));
        $this->assertContains("Variable 'NON_EXISTING_VAR' does not exist.", $this->getMessages('error'));
    }
}
