<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Laravel\Prompts\Prompt;
use Mumincacao\LaravelEnvManager\EnvManagerCommand;
use Mumincacao\LaravelEnvManager\ServiceProvider;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EnvManagerCommand::class)]
#[CoversClass(ServiceProvider::class)]
class EnvManagerCommandTest extends TestCase
{
    private const BASE_PATH = __DIR__ . '/../stubs/app';

    private const ENC_KEY = 'base64:yaf/zz3lILilEtaTIZX4rzuwGoPyFfEr6JWlrpGaT9M=';

    private const NEW_ENC_KEY = 'base64:RG+OTBW4nnBElp1ousGqMCu9j0XVjJOK+6f0xYdZ0VE=';

    private const ENV_CONTENT = <<<ENV
    APP_NAME="Laravel"
    APP_ENV="local"
    ENV;

    #[Override]
    public function createApplication()
    {
        $app = Application::configure(self::BASE_PATH)
            ->withProviders([ServiceProvider::class])
            ->withEvents()
            ->create();
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function testNoChanges(): void
    {
        $this->artisan('env:manager', ['env' => 'demo'])
            ->expectsQuestion('Select an action', 'finish')
            ->expectsOutput('No changes detected. .env file remains unchanged.')
            ->assertExitCode(0);
    }

    public function testDiscardChanges(): void
    {
        $this->artisan('env:manager', ['env' => 'demo'])
            ->expectsQuestion('Select an action', 'set')
            ->expectsQuestion('Enter variable name', 'APP_NAME')
            ->expectsQuestion('Enter variable value. (Current value: Laravel)', 'NewApp')
            ->expectsQuestion('Select an action', 'finish')
            ->expectsConfirmation('Do you want to save the changes to the .env file?', 'no')
            ->expectsOutput('No changes were saved.')
            ->assertExitCode(0);

        $this->assertStringEqualsFile(self::BASE_PATH . '/.env.demo', self::ENV_CONTENT);
    }

    public function testSaveChanges(): void
    {
        $this->artisan('env:manager', ['env' => 'demo'])
            ->expectsQuestion('Select an action', 'set')
            ->expectsQuestion('Enter variable name', 'APP_NAME')
            ->expectsQuestion('Enter variable value. (Current value: Laravel)', 'NewApp')
            ->expectsQuestion('Select an action', 'finish')
            ->expectsConfirmation('Do you want to save the changes to the .env file?', 'yes')
            ->expectsOutputToContain(self::ENC_KEY)
            ->expectsOutput("Changes saved to .env file for environment 'demo'.")
            ->assertExitCode(0);

        $expectedContent = <<<ENV
        APP_ENV="local"
        APP_NAME="NewApp"
        ENV;
        $this->assertStringEqualsFile(self::BASE_PATH . '/.env.demo', $expectedContent);
    }

    public function testInputEnvKey(): void
    {
        putenv('ENV_MAN_ENCRYPTION_KEY'); // Unset ENV_MAN_ENCRYPTION_KEY for this test

        $this->artisan('env:manager', ['env' => 'demo'])
            ->expectsQuestion('Enter encryption key for environment variables', self::NEW_ENC_KEY)
            ->expectsQuestion('Select an action', 'set')
            ->expectsQuestion('Enter variable name', 'APP_NAME')
            ->expectsQuestion('Enter variable value. (Current value: Laravel)', 'NewApp')
            ->expectsQuestion('Select an action', 'finish')
            ->expectsConfirmation('Do you want to save the changes to the .env file?', 'yes')
            ->expectsOutputToContain(self::NEW_ENC_KEY)
            ->expectsOutput("Changes saved to .env file for environment 'demo'.")
            ->assertExitCode(0);
    }

    public function testNoEnvKey(): void
    {
        putenv('ENV_MAN_ENCRYPTION_KEY'); // Unset ENV_MAN_ENCRYPTION_KEY for this test

        Prompt::fallbackWhen(true); // Fallback to default answers for any prompt
        $this->artisan('env:manager', ['env' => 'demo'])
            /**
             * @phpstan-ignore argument.type
             */
            ->expectsQuestion('Enter encryption key for environment variables', null) // Simulate no input for encryption key
            ->expectsQuestion('Select an action', 'set')
            ->expectsQuestion('Enter variable name', 'APP_NAME')
            ->expectsQuestion('Enter variable value. (Current value: Laravel)', 'NewApp')
            ->expectsQuestion('Select an action', 'finish')
            ->expectsConfirmation('Do you want to save the changes to the .env file?', 'yes')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Environment successfully encrypted.')
            ->expectsOutputToContain('base64:') // Check that a key was generated
            ->expectsOutput("Changes saved to .env file for environment 'demo'.")
            ->assertExitCode(0);
    }

    protected function setUp(): void
    {
        putenv('APP_KEY=base64:OKLQj30TYU0t9nTrSTdOgwkW8PVEF4ok9uk2FcRJh5E='); // Set APP_KEY for testing
        putenv('ENV_MAN_ENCRYPTION_KEY=' . self::ENC_KEY); // Set ENV_MAN_ENCRYPTION_KEY for testing
        file_put_contents(self::BASE_PATH . '/.env.demo', self::ENV_CONTENT);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach (glob(self::BASE_PATH . '/.env*') as $file) {
            unlink($file);
        }
        putenv('ENV_MAN_ENCRYPTION_KEY'); // Unset ENV_MAN_ENCRYPTION_KEY after testing
        putenv('APP_KEY'); // Unset APP_KEY after testing

        parent::tearDown();
    }
}
