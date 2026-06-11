<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

use Mumincacao\LaravelEnvManager\Contracts\Action;

class FinishAction extends Action
{
    public function description(): string
    {
        return 'Apply all changes to the .env file';
    }

    public function execute(): void
    {
        // NOP
    }

    public function isFinish(): bool
    {
        return true;
    }
}
