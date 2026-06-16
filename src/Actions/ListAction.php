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
        $this->printVars($keys);
    }

    /**
     * @param  list<string>  $keys
     */
    protected function printVars(array $keys): void
    {
        foreach ($keys as $key) {
            $value = $this->repository->get($key);
            $original = $this->repository->getOriginal($key);
            match ($this->repository->getStatus($key)) {
                EnvStatus::Added => $this->lineAdd($key, $value),
                EnvStatus::Modified => $this->lineModify($key, $value, $original),
                EnvStatus::Removed => $this->lineRemove($key, $original),
                EnvStatus::Keep => $this->lineKeep($key, $value),
            };
        }
    }

    protected function lineAdd(string $key, string $value): void
    {
        $this->handler->info("+ {$key}={$value}");
    }

    protected function lineRemove(string $key, string $original): void
    {
        $this->handler->error("- {$key}={$original}");
    }

    protected function lineModify(string $key, string $value, string $original): void
    {
        $this->lineRemove($key, $original);
        $this->lineAdd($key, $value);
    }

    protected function lineKeep(string $key, string $value): void
    {
        $this->handler->line("  {$key}={$value}");
    }
}
