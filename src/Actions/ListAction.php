<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

use Mumincacao\LaravelEnvManager\Contracts\Action;
use Mumincacao\LaravelEnvManager\Enums\EnvStatus;

class ListAction extends Action
{
    public function description(): string
    {
        return 'List all environment variables and their statuses';
    }

    public function execute(): void
    {
        $keys = $this->repository->keys();
        if (count($keys) === 0) {
            $this->handler->info('No environment variables found.');

            return;
        }

        $this->handler->info('Current environment variables:');
        foreach ($keys as $key) {
            $value = $this->repository->get($key);
            $original = $this->repository->getOriginal($key);
            match ($this->repository->getStatus($key)) {
                EnvStatus::Added => $this->handler->info("- A {$key}={$value}"),
                EnvStatus::Modified => $this->handler->warn("- M {$key}={$value} (original: {$original})"),
                EnvStatus::Removed => $this->handler->error("- D {$key}= (original: {$original})"),
                EnvStatus::Keep => $this->handler->line("-   {$key}={$value}"),
            };
        }
    }
}
