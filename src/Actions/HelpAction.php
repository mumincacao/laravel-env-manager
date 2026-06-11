<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Actions;

use Mumincacao\LaravelEnvManager\Contracts\Action;

class HelpAction extends Action
{
    public function description(): string
    {
        return 'Show this help message';
    }

    public function execute(): void
    {
        $this->handler->info('Available actions:');
        foreach ($this->handler->getDescriptions() as $key => $description) {
            $this->handler->line("- <comment>{$key}</comment>: {$description}");
        }
    }
}
