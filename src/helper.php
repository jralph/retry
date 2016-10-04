<?php

namespace Jralph\Retry;

/**
 * Retry a given callable a given number of times on failure.
 *
 * @param int|\Closure $attempts The number of attempts to try or a closure that should return true when to stop.
 * @param callable $callable
 * @return int
 */
function retry($attempts, callable $callable)
{
    $retry = new Retry;

    if (is_numeric($attempts)) {
        $retry->retries($attempts);
    } else if ($attempts instanceof \Closure) {
        $retry->forever()->until($attempts);
    }

    if ($callable instanceof \Closure) {
        $retry->command($callable);
    } else {
        $retry->command(function () use ($callable) {
            return call_user_func($callable);
        });
    }

    return $retry->run();
}
