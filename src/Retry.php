<?php

namespace Jralph\Retry;

class Retry
{
    /**
     * The command to execute.
     *
     * @var callable
     */
    protected $command;

    /**
     * The number of attempts to make.
     *
     * @var int
     */
    protected $retries = 1;

    /**
     * The current attempt;
     *
     * @var int
     */
    protected $attempt = 0;

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
    protected $wait;

    /**
     * Set the command to be run.
     *
     * @param callable $command
     * @return Retry
     */
    public function command(callable $command) : Retry
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Set the number of retries to attempt.
     *
     * @param int $retries
     * @return Retry
     */
    public function attempts(int $retries) : Retry
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * Retry once.
     *
     * @return Retry
     */
    public function once() : Retry
    {
        return $this->attempts(1);
    }

    /**
     * Retry twice.
     *
     * @return Retry
     */
    public function twice() : Retry
    {
        return $this->attempts(2);
    }

    /**
     * Retry thrice.
     *
     * @return Retry
     */
    public function thrice() : Retry
    {
        return $this->attempts(3);
    }

    /**
     * Retry forever.
     *
     * @return Retry
     */
    public function forever() : Retry
    {
        return $this->attempts(0);
    }

    /**
     * Retry until a closure returns truthfully.
     * The closure accepts the number of current attempts as the first argument and
     * the result of the last attempt as the second argument.
     *
     * @param callable $until
     * @return Retry
     */
    public function until(callable $until) : Retry
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
    public function onlyIf(callable $callback) : Retry
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
    public function onError(callable $callback) : Retry
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
    public function wait(int $seconds) : Retry
    {
        $this->wait = $seconds;

        return $this;
    }

    /**
     * Run the command and return the number of retries it took
     * or throw an exception if the process failed.
     *
     * @throws RetryException
     * @return mixed
     */
    public function run()
    {
        return $this->try();
    }

    /**
     * Attempt
     * @return mixed
     * @throws RetryException
     */
    protected function try()
    {
        $this->attempt++;

        try {
            $result = $this->callCommand();
        } catch (\Throwable $thrown) {
            $result = $thrown;
        }

        if ($this->shouldRetry($result)) {
            if ($this->onError) {
                $this->callOnError($result);
            }

            if ($this->wait) {
                $start = time();
                while (time() < $start + 3) {}
            }

            return $this->try();
        } else if ($result instanceof \Throwable) {
            if ($this->onError) {
                $this->callOnError($result);
            }

            throw new RetryException(
                "Maximum number of retries reached. ($this->attempt/$this->retries)",
                0,
                $result
            );
        }

        return $result;
    }

    /**
     * Call the provided command and return its response.
     *
     * @return mixed
     */
    protected function callCommand()
    {
        return call_user_func($this->command, $this->attempt);
    }

    /**
     * Call the provided on error handler.
     *
     * @param $result
     * @return mixed
     */
    protected function callOnError($result)
    {
        return call_user_func($this->onError, $this->attempt, $result);
    }

    /**
     * Determine if a retry should is possible or not.
     *
     * @param $response
     * @return bool
     */
    protected function shouldRetry($response) : bool
    {
        return $this->retryAvailable() && $this->passesOnlyIf($response) && !$this->reachedUntil($response);
    }

    /**
     * Does the retry result pass the only if callback?
     *
     * @param $response
     * @return bool
     */
    protected function passesOnlyIf($response) : bool
    {
        if ($this->onlyIf) {
            return $this->callOnlyIf($response);
        }

        return !$this->isSuccessful($response);
    }

    /**
     * Call the onlyIf handler.
     *
     * @param $response
     * @return mixed
     */
    protected function callOnlyIf($response)
    {
        return call_user_func($this->onlyIf, $this->attempt, $response);
    }

    /**
     * Determine if the command was successful.
     *
     * @param $response
     * @return bool
     */
    protected function isSuccessful($response) : bool
    {
        if ($response instanceof \Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Have we reached the custom defined until callback?
     *
     * @param $response
     * @return bool
     */
    protected function reachedUntil($response) : bool
    {
        if ($this->until) {
            return $this->callUntil($response);
        }

        return false;
    }

    /**
     * Call the until handler.
     *
     * @param $response
     * @return bool
     */
    protected function callUntil($response)
    {
        return (bool) call_user_func($this->until, $this->attempt, $response);
    }

    /**
     * Determine if a retry is available.
     *
     * @return bool
     */
    protected function retryAvailable()
    {
        return $this->attempt < $this->retries || ! $this->retries || $this->retries == INF;
    }
}
