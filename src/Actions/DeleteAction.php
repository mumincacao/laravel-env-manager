<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

use Mumincacao\LaravelEnvManager\Contracts\Action;

class DeleteAction extends Action
{
    public function description(): string
    {
        return 'Delete an environment variable';
    }

    public function execute(): void
    {
        $name = strtoupper(
            $this->handler->anticipate('Enter delete variable name', array_keys($this->repository->all()))
        );
        if ($this->repository->has($name) === false) {
            $this->handler->error("Variable '{$name}' does not exist.");

            return;
        }
        $this->repository->remove($name);
        $this->handler->info("Variable '{$name}' has been deleted.");
    }
}
