<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\SetAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(SetAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionSetTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('Set or update an environment variable', SetAction::class);
    }

    public function testExecute(): void
    {
        $handler = $this->createHandler();
        $this->setAnticipateResponse('TEST_VAR');
        $this->setAskResponse('value');

        $isFinish = $handler->handle('set');

        $this->assertFalse($isFinish);
        $this->assertTrue($this->repository->has('TEST_VAR'));
        $this->assertSame('value', $this->repository->get('TEST_VAR'));
    }

    public function testInvalidKey(): void
    {
        $handler = $this->createHandler();
        $this->setAnticipateResponse('INVALID KEY');
        $isFinish = $handler->handle('set');

        $this->assertFalse($isFinish);
        $this->assertFalse($this->repository->has('INVALID KEY'));
        $this->assertContains(
            "Invalid variable name. Allowed only uppercase letters, numbers, and underscores.",
            $this->getMessages('error')
        );
    }

    public function testEmptyKey(): void
    {
        $handler = $this->createHandler();
        $this->setAnticipateResponse('');
        $isFinish = $handler->handle('set');

        $this->assertFalse($isFinish);
        $this->assertContains(
            "Invalid variable name. Allowed only uppercase letters, numbers, and underscores.",
            $this->getMessages('error')
        );
    }

    public function testNullKey(): void
    {
        $handler = $this->createHandler();
        $this->setAnticipateResponse(null);
        $isFinish = $handler->handle('set');

        $this->assertFalse($isFinish);
        $this->assertContains(
            "Invalid variable name. Allowed only uppercase letters, numbers, and underscores.",
            $this->getMessages('error')
        );
    }

    public function testEmptyValue(): void
    {
        $handler = $this->createHandler();
        $this->setAnticipateResponse('TEST_VAR');
        $this->setAskResponse('');

        $isFinish = $handler->handle('set');

        $this->assertFalse($isFinish);
        $this->assertTrue($this->repository->has('TEST_VAR'));
        $this->assertSame('', $this->repository->get('TEST_VAR'));
    }

    public function testNullValue(): void
    {
        $handler = $this->createHandler();
        $this->setAnticipateResponse('TEST_VAR');
        $this->setAskResponse(null);

        $isFinish = $handler->handle('set');

        $this->assertFalse($isFinish);
        $this->assertTrue($this->repository->has('TEST_VAR'));
        $this->assertNull($this->repository->get('TEST_VAR'));
    }
}
