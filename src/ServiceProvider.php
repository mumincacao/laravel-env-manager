<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager;

use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EnvManagerCommand::class,
            ]);
        }
    }
}
