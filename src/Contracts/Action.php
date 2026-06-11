<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Contracts;

use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;

abstract class Action
{
    public function __construct(
        protected readonly EnvRepository $repository,
        protected readonly EnvActionHandler $handler,
    ) {
        // NOP
    }

    abstract public function description(): string;

    abstract public function execute(): void;

    public function isFinish(): bool
    {
        return false;
    }
}
