<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager;

use Illuminate\Console\Command;
use Mumincacao\LaravelEnvManager\Contracts\CommandProxy;

class EnvManagerCommand extends Command implements CommandProxy
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $signature = <<<'SIGNATURE'
        env:manager
        {env : Target environment}
        {--clean : Clean up .env file after encryption}
    SIGNATURE;

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $description = <<<'DESCRIPTION'
        Manage encrypted environment variables.
        Encryption key will be prompted if ENV_MAN_ENCRYPTION_KEY is not set.
    DESCRIPTION;

    public function handle(): void
    {
        $env = $this->argument('env');
        $key = env('ENV_MAN_ENCRYPTION_KEY') ?? $this->secret('Enter encryption key for environment variables');

        $handler = new EnvFileHandler(base_path(), $env, $key, $this);
        $repository = $handler->load();
        $this->modifyEnv($repository);

        if ($repository->isClean()) {
            $this->info('No changes detected. .env file remains unchanged.');

            return;
        }

        if ($this->confirm('Do you want to save the changes to the .env file?')) {
            $handler->save($repository, $this->option('clean'));
            $this->info("Changes saved to .env file for environment '{$env}'.");
        } else {
            $this->info('No changes were saved.');
        }
    }

    private function modifyEnv(EnvRepository $repository): void
    {
        $handler = new EnvActionHandler($repository, $this);
        $actions = $handler->getDescriptions();
        while (true) {
            $type = $this->choice('Select an action', $actions);
            if ($handler->handle($type)) {
                break;
            }
        }
    }
}
