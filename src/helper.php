<?php

namespace Jralph\Retry;

/**
 * Retry a given callable a given number of times on failure.
 *
 * @param int|\Closure $attempts The number of attempts to try or a closure that should return true when to stop.
 * @param callable $command
 * @param callable $onError
 * @return int
 */
function retry($attempts, callable $command, callable $onError = null)
{
    $retry = new Retry;

    if (is_numeric($attempts)) {
        $retry->attempts($attempts);
    } else if ($attempts instanceof \Closure) {
        $retry->forever()->until($attempts);
    }

    if ($onError) {
        if ($onError instanceof \Closure) {
            $retry->onError($onError);
        } else {
            $retry->onError(function () use ($onError) {
                return call_user_func($onError);
            });
        }
    }

    if ($command instanceof \Closure) {
        $retry->command($command);
    } else {
        $retry->command(function () use ($command) {
            return call_user_func($command);
        });
    }

    return $retry->run();
}
