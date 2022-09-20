<?php

namespace Jralph\Retry;

class Retry
{
    /**
     * The command to execute.
     *
     * @var Command
     */
    protected Command $command;

    /**
     * The number of attempts to make.
     *
     * @var int
     */
    protected int $retries = 1;

    /**
     * The current attempt;
     *
     * @var int
     */
    protected int $attempt = 0;

    /**
     * A closure to determine if a retry should be attempted.
     *
     * @var callable
     */
    protected $onlyIf;

    /**
     * A closure to determine if the retries should stop.
     *
     * @var callable
     */
    protected $until;

    /**
     * A closure to run each time the command errors.
     *
     * @var callable
     */
    protected $onError;

    /**
     * How long to wait before retrying.
     *
     * @var int
     */
    protected int $wait;

    public function __construct(?Command $command = null)
    {
        if ($command) {
            $this->setCommand($command);
        }
    }

    /**
     * Set the command to be run.
     *
     * @param callable|Command $command
     * @return void
     */
    protected function setCommand(callable|Command $command): void
    {
        if ($command instanceof Command) {
            $this->command = $command;
        } else {
            $this->command = new Command($command);
        }
    }

    /**
     * Set the number of retries to attempt.
     *
     * @param int $retries
     * @return Retry
     */
    public function attempts(int $retries): Retry
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * Retry until a closure returns truthfully.
     * The closure accepts the number of current attempts as the first argument and
     * the result of the last attempt as the second argument.
     *
     * @param callable $until
     * @return Retry
     */
    public function until(callable $until): Retry
    {
        $this->until = $until;

        return $this;
    }

    /**
     * Set a callback to check if a retry should be attempted or not.
     *
     * @param callable $callback A callback that accepts the response/throwable from the attempt at the first param.
     * @return Retry
     */
    public function onlyIf(callable $callback): Retry
    {
        $this->onlyIf = $callback;

        return $this;
    }

    /**
     * Set a callback to run each time the command fails.
     *
     * @param callable $callback
     * @return Retry
     */
    public function onError(callable $callback): Retry
    {
        $this->onError = $callback;

        return $this;
    }

    /**
     * Wait for the given number of seconds before retrying.
     *
     * @param int $seconds
     * @return Retry
     */
    public function wait(int $seconds): Retry
    {
        $this->wait = $seconds;

        return $this;
    }

    /**
     * Run the command and return the number of retries it took
     * or throw an exception if the process failed.
     *
     * @return mixed
     * @throws RetryException
     */
    public function run(): mixed
    {
        $this->attempt++;

        $result = $this->getResult();

        if ($this->shouldRetry($result)) {
            return $this->handleRetry($result);
        }

        if ($result instanceof \Throwable) {
            $this->handleThrowable($result);
        }

        return $result;
    }

    /**
     * Call the command and get the result, or return the thrown throwable.
     *
     * @return mixed
     */
    protected function getResult(): mixed
    {
        try {
            return $this->command->run($this->attempt);
        } catch (\Throwable $thrown) {
            return $thrown;
        }
    }

    /**
     * Run a retry for a given result.
     *
     * @param mixed $result
     * @return mixed
     * @throws RetryException
     */
    protected function handleRetry(mixed $result): mixed
    {
        if ($this->onError) {
            $this->callOnError($result);
        }

        if ($this->wait) {
            sleep($this->wait);
        }

        return $this->run();
    }

    /**
     * Handle a throwable that could not be retried.
     *
     * @param \Throwable $result
     * @return void
     * @throws RetryException
     */
    protected function handleThrowable(\Throwable $result): void
    {
        if ($this->onError) {
            $this->callOnError($result);
        }

        throw new RetryException(
            sprintf('Maximum number of retries reached. (%d/%d)', $this->attempt, $this->retries),
            0,
            $result
        );
    }

    /**
     * Call the provided on error handler.
     *
     * @param mixed $result
     * @return mixed
     */
    protected function callOnError(mixed $result): mixed
    {
        return call_user_func($this->onError, $this->attempt, $result);
    }

    /**
     * Determine if a retry should is possible or not.
     *
     * @param mixed $response
     * @return bool
     */
    protected function shouldRetry(mixed $response): bool
    {
        return $this->retryAvailable() && $this->passesOnlyIf($response) && !$this->reachedUntil($response);
    }

    /**
     * Does the retry result pass the only if callback?
     *
     * @param mixed $response
     * @return bool
     */
    protected function passesOnlyIf(mixed $response): bool
    {
        if ($this->onlyIf) {
            return $this->callOnlyIf($response);
        }

        return !$this->isSuccessful($response);
    }

    /**
     * Call the onlyIf handler.
     *
     * @param mixed $response
     * @return mixed
     */
    protected function callOnlyIf(mixed $response): mixed
    {
        return call_user_func($this->onlyIf, $this->attempt, $response);
    }

    /**
     * Determine if the command was successful.
     *
     * @param mixed $response
     * @return bool
     */
    protected function isSuccessful(mixed $response): bool
    {
        return !($response instanceof \Throwable);
    }

    /**
     * Have we reached the custom defined until callback?
     *
     * @param mixed $response
     * @return bool
     */
    protected function reachedUntil(mixed $response): bool
    {
        if ($this->until) {
            return $this->callUntil($response);
        }

        return false;
    }

    /**
     * Call the until handler.
     *
     * @param mixed $response
     * @return bool
     */
    protected function callUntil(mixed $response): bool
    {
        return (bool)call_user_func($this->until, $this->attempt, $response);
    }

    /**
     * Determine if a retry is available.
     *
     * @return bool
     */
    protected function retryAvailable(): bool
    {
        return $this->attempt < $this->retries || $this->retries === 0;
    }
}
