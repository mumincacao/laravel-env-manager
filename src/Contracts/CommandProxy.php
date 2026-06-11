<?php

declare(strict_types=1);

namespace Mumincacao\LaravelEnvManager\Contracts;

use Throwable;

interface CommandProxy
{
    /**
     * @return void
     */
    public function line(string $message);

    /**
     * @return void
     */
    public function info(string $message);

    /**
     * @return void
     */
    public function warn(string $message);

    /**
     * @return void
     */
    public function error(string $message);

    /**
     * @return string
     */
    public function ask(string $question);

    /**
     * @return bool
     */
    public function confirm(string $question);

    /**
     * @return string
     */
    public function anticipate(string $question, array $choices);

    /**
     * @return void
     */
    public function fail(Throwable|string|null $exception = null);

    /**
     * @param  string  $command
     *
     * @return int
     */
    public function call($command, array $arguments = []);
}
