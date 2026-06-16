<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager;

use Illuminate\Support\Arr;
use Mumincacao\LaravelEnvManager\Actions\ChangesAction;
use Mumincacao\LaravelEnvManager\Actions\DeleteAction;
use Mumincacao\LaravelEnvManager\Actions\FinishAction;
use Mumincacao\LaravelEnvManager\Actions\GrepAction;
use Mumincacao\LaravelEnvManager\Actions\HelpAction;
use Mumincacao\LaravelEnvManager\Actions\ListAction;
use Mumincacao\LaravelEnvManager\Actions\ResetAction;
use Mumincacao\LaravelEnvManager\Actions\SetAction;
use Mumincacao\LaravelEnvManager\Contracts\Action;
use Mumincacao\LaravelEnvManager\Contracts\CommandProxy;
use Mumincacao\LaravelEnvManager\Enums\Actions;

/**
 * @mixin CommandProxy
 */
class EnvActionHandler
{
    private const ACTION_MAP = [
        Actions::Changes->value => ChangesAction::class,
        Actions::Delete->value => DeleteAction::class,
        Actions::Finish->value => FinishAction::class,
        Actions::Grep->value => GrepAction::class,
        Actions::Help->value => HelpAction::class,
        Actions::List->value => ListAction::class,
        Actions::Reset->value => ResetAction::class,
        Actions::Set->value => SetAction::class,
    ];

    /**
     * @var array<value-of<Actions>, Action>
     */
    private readonly array $actions;

    public function __construct(
        private readonly EnvRepository $repository,
        private readonly CommandProxy $command,
    ) {
        $this->actions = Arr::mapWithKeys(
            Actions::cases(),
            function (Actions $action) {
                $name = self::ACTION_MAP[$action->value];

                return [$action->value => new $name($this->repository, $this)];
            }
        );
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->command->{$name}(...$arguments);
    }

    /**
     * @return array<value-of<Actions>, string>
     */
    public function getDescriptions(): array
    {
        return Arr::mapWithKeys(
            $this->actions,
            fn ($action, $key) => [$key => $action->description()]
        );
    }

    /**
     * @param  value-of<Actions>  $actionKey
     *
     * @return bool Returns true if the action indicates to finish the session, false otherwise.
     */
    public function handle(string $actionKey): bool
    {
        if (isset($this->actions[$actionKey]) === false) {
            $this->command->error("Invalid action: '{$actionKey}'. Please choose a valid action.");

            return false;
        }

        $action = $this->actions[$actionKey];
        $action->execute();

        return $action->isFinish();
    }
}
