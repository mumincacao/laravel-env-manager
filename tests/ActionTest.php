<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests;

use Mumincacao\LaravelEnvManager\Contracts\Action;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\Mocks\MockCommand;
use PHPUnit\Framework\TestCase;

abstract class ActionTest extends TestCase
{
    protected EnvRepository $repository;

    private MockCommand $command;

    /**
     * @param  class-string<Action>  $actionClass
     */
    protected function assertDescription(string $expected, string $actionClass): void
    {
        $repository = new EnvRepository([]);
        $handler = new EnvActionHandler($repository, new MockCommand());
        $action = new $actionClass($repository, $handler);

        $this->assertSame($expected, $action->description());
    }

    protected function createHandler(array $vars = []): EnvActionHandler
    {
        $this->repository = new EnvRepository($vars);
        $this->command = new MockCommand();

        return new EnvActionHandler($this->repository, $this->command);
    }

    protected function setAskResponse(string $response): void
    {
        $this->command->setAskResponse($response);
    }

    protected function setConfirmationResponse(bool $response): void
    {
        $this->command->setConfirmationResponse($response);
    }

    protected function setAnticipateResponse(string $response): void
    {
        $this->command->setAnticipateResponse($response);
    }

    /**
     * @param 'line'|'info'|'warn'|'error'|'ask'|'confirm'|'anticipate' $type
     *
     * @return list<string>
     */
    protected function getMessages(string $type): array
    {
        return $this->command->getMessages($type);
    }
}
