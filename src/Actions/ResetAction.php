<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

use Mumincacao\LaravelEnvManager\Contracts\Action;

class ResetAction extends Action
{
    public function description(): string
    {
        return 'Reset all changes to the original environment variables';
    }

    public function execute(): void
    {
        if ($this->handler->confirm('Are you sure you want to reset all changes?') === false) {
            $this->handler->info('Reset action cancelled.');

            return;
        }
        $this->repository->reset();
        $this->handler->info('All changes have been reset to the original environment variables.');
    }
}
