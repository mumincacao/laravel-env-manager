<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Unit;

use Mumincacao\LaravelEnvManager\Actions\ChangesAction;
use Mumincacao\LaravelEnvManager\Actions\DeleteAction;
use Mumincacao\LaravelEnvManager\Actions\FinishAction;
use Mumincacao\LaravelEnvManager\Actions\HelpAction;
use Mumincacao\LaravelEnvManager\Actions\ListAction;
use Mumincacao\LaravelEnvManager\Actions\ResetAction;
use Mumincacao\LaravelEnvManager\Actions\SetAction;
use Mumincacao\LaravelEnvManager\EnvActionHandler;
use Mumincacao\LaravelEnvManager\EnvRepository;
use Mumincacao\LaravelEnvManager\tests\Mocks\MockCommand;
use Mumincacao\LaravelEnvManager\tests\Mocks\MockFailException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvActionHandler::class)]
#[UsesClass(DeleteAction::class)]
#[UsesClass(FinishAction::class)]
#[UsesClass(HelpAction::class)]
#[UsesClass(ListAction::class)]
#[UsesClass(ChangesAction::class)]
#[UsesClass(ResetAction::class)]
#[UsesClass(SetAction::class)]
class EnvActionHandlerTest extends TestCase
{
    public function testGetDescriptions(): void
    {
        $handler = new EnvActionHandler(new EnvRepository([]), new MockCommand());

        $descriptions = $handler->getDescriptions();

        $this->assertArrayHasKey('delete', $descriptions);
        $this->assertArrayHasKey('finish', $descriptions);
        $this->assertArrayHasKey('help', $descriptions);
        $this->assertArrayHasKey('list', $descriptions);
        $this->assertArrayHasKey('changes', $descriptions);
        $this->assertArrayHasKey('reset', $descriptions);
        $this->assertArrayHasKey('set', $descriptions);

        foreach ($descriptions as $description) {
            $this->assertNotEmpty($description);
        }
    }

    public static function outputMethodsProvider(): array
    {
        return [
            ['line', 'Test line message'],
            ['info', 'Test info message'],
            ['warn', 'Test warn message'],
            ['error', 'Test error message'],
        ];
    }

    #[DataProvider('outputMethodsProvider')]
    public function testOutputMethods(string $method, string $message): void
    {
        $command = new MockCommand();
        $handler = new EnvActionHandler(new EnvRepository([]), $command);
        $handler->{$method}($message);

        $this->assertCount(1, $command->getMessages($method));
        $this->assertContains($message, $command->getMessages($method));

        $handler->{$method}($message . ' again');

        $this->assertCount(2, $command->getMessages($method));
        $this->assertContains($message, $command->getMessages($method));
        $this->assertContains($message . ' again', $command->getMessages($method));
    }

    public function testAskMethod(): void
    {
        $command = new MockCommand();
        $command->setAskResponse('Test Taro');
        $command->setAskResponse('Fine, thank you.');
        $handler = new EnvActionHandler(new EnvRepository([]), $command);
        $response = $handler->ask('What is your name?');

        $this->assertSame('Test Taro', $response);
        $this->assertCount(1, $command->getMessages('ask'));
        $this->assertContains('What is your name?', $command->getMessages('ask'));

        $newResponse = $handler->ask('How are you?');
        $this->assertSame('Fine, thank you.', $newResponse);
        $this->assertCount(2, $command->getMessages('ask'));
        $this->assertContains('What is your name?', $command->getMessages('ask'));
        $this->assertContains('How are you?', $command->getMessages('ask'));

        $otherResponse = $handler->ask('What is your favorite color?');
        $this->assertNull($otherResponse);
        $this->assertCount(3, $command->getMessages('ask'));
        $this->assertContains('What is your name?', $command->getMessages('ask'));
        $this->assertContains('How are you?', $command->getMessages('ask'));
        $this->assertContains('What is your favorite color?', $command->getMessages('ask'));
    }

    public function testConfirmMethod(): void
    {
        $command = new MockCommand();
        $handler = new EnvActionHandler(new EnvRepository([]), $command);

        $command->setConfirmationResponse(true);
        $response = $handler->confirm('Do you want to continue?');

        $this->assertTrue($response);
        $this->assertCount(1, $command->getMessages('confirm'));
        $this->assertContains('Do you want to continue?', $command->getMessages('confirm'));

        $command->setConfirmationResponse(false);
        $newResponse = $handler->confirm('Are you sure?');
        $this->assertFalse($newResponse);
        $this->assertCount(2, $command->getMessages('confirm'));
        $this->assertContains('Do you want to continue?', $command->getMessages('confirm'));
        $this->assertContains('Are you sure?', $command->getMessages('confirm'));
    }

    public function testAnticipateMethod(): void
    {
        $command = new MockCommand();
        $command->setAnticipateResponse('Option 1');
        $command->setAnticipateResponse('Red');
        $handler = new EnvActionHandler(new EnvRepository([]), $command);
        $response = $handler->anticipate('Choose an option:', ['Option 1', 'Option 2']);

        $this->assertSame('Option 1', $response);
        $this->assertCount(1, $command->getMessages('anticipate'));
        $this->assertContains('Choose an option:', $command->getMessages('anticipate'));

        $newResponse = $handler->anticipate('Select a color:', ['Red', 'Green', 'Blue']);
        $this->assertSame('Red', $newResponse);
        $this->assertCount(2, $command->getMessages('anticipate'));
        $this->assertContains('Choose an option:', $command->getMessages('anticipate'));
        $this->assertContains('Select a color:', $command->getMessages('anticipate'));

        $otherResponse = $handler->anticipate('Pick a fruit:', ['Apple', 'Banana', 'Cherry']);
        $this->assertNull($otherResponse);
        $this->assertCount(3, $command->getMessages('anticipate'));
        $this->assertContains('Choose an option:', $command->getMessages('anticipate'));
        $this->assertContains('Select a color:', $command->getMessages('anticipate'));
        $this->assertContains('Pick a fruit:', $command->getMessages('anticipate'));
    }

    public function testFailMethod(): void
    {
        $handler = new EnvActionHandler(new EnvRepository([]), new MockCommand());

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage('This is a failure message');

        $handler->fail('This is a failure message');
    }

    public function testFailMethodWithException(): void
    {
        $handler = new EnvActionHandler(new EnvRepository([]), new MockCommand());

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage('This is an exception message');

        $handler->fail(new MockFailException('This is an exception message'));
    }

    public function testFailMethodWithNull(): void
    {
        $handler = new EnvActionHandler(new EnvRepository([]), new MockCommand());

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage('');

        $handler->fail(null);
    }

    public function testFailMethodWithEmptyString(): void
    {
        $handler = new EnvActionHandler(new EnvRepository([]), new MockCommand());

        $this->expectException(MockFailException::class);
        $this->expectExceptionMessage('');

        $handler->fail('');
    }

    public function testCallDeleteAction(): void
    {
        $repository = new EnvRepository(['TEST_KEY' => 'test_value']);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Simulate user input for variable name
        $command->setAnticipateResponse('TEST_KEY');

        // Call the delete action
        $isFinish = $handler->handle('delete');

        $this->assertFalse($isFinish);
    }

    public function testCallFinishAction(): void
    {
        $repository = new EnvRepository([]);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Call the finish action
        $isFinish = $handler->handle('finish');

        $this->assertTrue($isFinish);
    }

    public function testCallHelpAction(): void
    {
        $repository = new EnvRepository([]);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Call the help action
        $isFinish = $handler->handle('help');

        $this->assertFalse($isFinish);
    }

    public function testCallListAction(): void
    {
        $repository = new EnvRepository(['TEST_KEY' => 'test_value']);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Call the list action
        $isFinish = $handler->handle('list');

        $this->assertFalse($isFinish);
    }

    public function testCallChangesAction(): void
    {
        $repository = new EnvRepository(['TEST_KEY' => 'test_value']);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Call the changes action
        $isFinish = $handler->handle('changes');

        $this->assertFalse($isFinish);
    }

    public function testCallGrepAction(): void
    {
        $repository = new EnvRepository(['TEST_KEY' => 'test_value']);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Simulate user input for search keyword
        $command->setAskResponse('TEST');

        // Call the grep action
        $isFinish = $handler->handle('grep');

        $this->assertFalse($isFinish);
    }

    public function testCallResetAction(): void
    {
        $repository = new EnvRepository(['TEST_KEY' => 'test_value']);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Call the reset action
        $isFinish = $handler->handle('reset');

        $this->assertFalse($isFinish);
    }

    public function testCallSetAction(): void
    {
        $repository = new EnvRepository([]);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        // Simulate user input for variable name and value
        $command->setAskResponse('TEST_KEY');
        $command->setAnticipateResponse('test_value');

        // Call the set action
        $isFinish = $handler->handle('set');

        $this->assertFalse($isFinish);
    }

    public function testCallInvalidAction(): void
    {
        $repository = new EnvRepository([]);
        $command = new MockCommand();
        $handler = new EnvActionHandler($repository, $command);

        /**
         * @phpstan-ignore argument.type
         */
        $isFinish = $handler->handle('invalid_action');

        $this->assertFalse($isFinish);
        $this->assertContains("Invalid action: 'invalid_action'. Please choose a valid action.", $command->getMessages('error'));
    }
}
