<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

use Mumincacao\LaravelEnvManager\Contracts\Action;

class SetAction extends Action
{
    public function description(): string
    {
        return 'Set or update an environment variable';
    }

    public function execute(): void
    {
        $name = strtoupper($this->handler->anticipate('Enter variable name', $this->repository->keys()) ?? '');
        if (preg_match('/\A[A-Z0-9_]+\z/', $name) !== 1) {
            $this->handler->error('Invalid variable name. Allowed only uppercase letters, numbers, and underscores.');

            return;
        }
        $value = $this->handler->ask("Enter variable value. (Current value: {$this->repository->get($name)})");

        $this->repository->set($name, $value);
        $this->handler->info("Set variable: {$name}={$value}");
    }
}
