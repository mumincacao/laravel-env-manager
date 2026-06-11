<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\HelpAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\ActionTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(HelpAction::class)]
#[UsesClass(EnvRepository::class)]
#[UsesClass(EnvActionHandler::class)]
class ActionHelpTest extends ActionTest
{
    public function testDescription(): void
    {
        $this->assertDescription('Show this help message', HelpAction::class);
    }

    public function testExecute(): void
    {
        $handler = $this->createHandler();
        $isFinish = $handler->handle('help');

        $this->assertFalse($isFinish);
        $this->assertContains('Available actions:', $this->getMessages('info'));
        foreach ($handler->getDescriptions() as $key => $description) {
            $this->assertContains("- <comment>{$key}</comment>: {$description}", $this->getMessages('line'));
        }
    }
}
