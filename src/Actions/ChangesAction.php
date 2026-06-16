<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

use Mumincacao\LaravelEnvManager\Enums\EnvStatus;

class ChangesAction extends ListAction
{
    public function description(): string
    {
        return 'List only changed environment variables';
    }

    public function execute(): void
    {
        $keys = $this->repository->keys();
        if (count($keys) === 0) {
            $this->handler->info('No environment variables found.');

            return;
        }

        $this->handler->info('Changed environment variables:');
        $changedKeys = array_filter($keys, fn ($key) => $this->repository->getStatus($key) !== EnvStatus::Keep);
        if (count($changedKeys) === 0) {
            $this->handler->info('No changes detected.');

            return;
        }

        $this->printVars($changedKeys);
    }
}
