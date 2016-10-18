<?php

namespace Jralph\Retry;

class Retry
{
    /**
     * The command to execute.
     *
     * @var \Closure
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
     * @var \Closure
     */
    protected $onlyIf;

    /**
     * A closure to determine if the retries should stop.
     *
     * @var \Closure
     */
    protected $until;

    /**
     * A closure to run each time the command errors.
     *
     * @var \Closure
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
     * @param \Closure $command
     * @return Retry
     */
    public function command(\Closure $command) : Retry
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
     * @param \Closure $until
     * @return Retry
     */
    public function until(\Closure $until) : Retry
    {
        $this->until = $until;

        return $this;
    }

    /**
     * Set a callback to check if a retry should be attempted or not.
     *
     * @param \Closure $callback A callback that accepts the response/throwable from the attempt at the first param.
     * @return Retry
     */
    public function onlyIf(\Closure $callback) : Retry
    {
        $this->onlyIf = $callback;

        return $this;
    }

    /**
     * Set a callback to run each time the command fails.
     *
     * @param \Closure $callback
     * @return Retry
     */
    public function onError(\Closure $callback) : Retry
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
            $command = $this->command;

            $result = $command($this->attempt);
        } catch (\Throwable $thrown) {
            $result = $thrown;
        }

        if ($this->shouldRetry($result)) {
            if ($onError = $this->onError) {
                $onError($this->attempt, $result);
            }

            if ($this->wait) {
                sleep($this->wait);
            }

            return $this->try();
        } else if ($result instanceof \Throwable) {
            if ($onError = $this->onError) {
                $onError($this->attempt, $result);
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
        if ($onlyIf = $this->onlyIf) {
            return $onlyIf($this->attempt, $response);
        }

        return !$this->isSuccessful($response);
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
        if ($until = $this->until) {
            return (bool) $until($this->attempt, $response);
        }

        return false;
    }

    /**
     * Determine if a retry is available.
     *
     * @return bool
     */
    protected function retryAvailable()
    {
        return $this->attempt < $this->retries || $this->retries == false || $this->retries == INF;
    }
}
