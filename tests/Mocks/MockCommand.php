<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\tests\Mocks;

use Mumincacao\LaravelEnvManager\Contracts\CommandProxy;
use Throwable;

class MockCommand implements CommandProxy
{
    /**
     * @var array<'line'|'info'|'warn'|'error'|'ask'|'confirm'|'anticipate', list<string>>
     */
    private array $messages = [];

    /**
     * @var list<?string>
     */
    private array $askResponse = [];

    /**
     * @var list<bool>
     */
    private array $confirmationResponse = [];

    /**
     * @var list<?string>
     */
    private array $anticipateResponse = [];

    /**
     * @var int|callable
     */
    private $callback = 0;

    public function line(string $message)
    {
        $this->messages['line'][] = $message;
    }

    public function info(string $message)
    {
        $this->messages['info'][] = $message;
    }

    public function warn(string $message)
    {
        $this->messages['warn'][] = $message;
    }

    public function error(string $message)
    {
        $this->messages['error'][] = $message;
    }

    public function ask(string $question)
    {
        $this->messages['ask'][] = $question;

        return array_shift($this->askResponse) ?? "Asked: {$question}";
    }

    public function confirm(string $question)
    {
        $this->messages['confirm'][] = $question;

        return array_shift($this->confirmationResponse) ?? false;
    }

    public function anticipate(string $question, array $choices)
    {
        $this->messages['anticipate'][] = $question;

        return array_shift($this->anticipateResponse) ?? "Anticipated: {$question} with choices " . implode(', ', $choices);
    }

    public function fail(Throwable|string|null $exception = null)
    {
        throw new MockFailException($exception instanceof Throwable ? $exception->getMessage() : "{$exception}");
    }

    public function call($command, array $arguments = [])
    {
        return is_callable($this->callback) ? ($this->callback)($command, $arguments) : $this->callback;
    }

    // ---- Helper methods for testing ----

    public function setAskResponse(?string $response): void
    {
        $this->askResponse[] = $response;
    }

    public function setConfirmationResponse(bool $response): void
    {
        $this->confirmationResponse[] = $response;
    }

    public function setAnticipateResponse(?string $response): void
    {
        $this->anticipateResponse[] = $response;
    }

    public function setCallResponse(int|callable $response): void
    {
        $this->callback = $response;
    }

    /**
     * @param  'line'|'info'|'warn'|'error'|'ask'|'confirm'|'anticipate' $type
     *
     * @return list<string>
     */
    public function getMessages(string $type): array
    {
        return $this->messages[$type] ?? [];
    }
}
