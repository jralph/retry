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
    } else if (is_callable($attempts)) {
        $retry->forever()->until($attempts);
    }

    if ($onError) {
        $retry->onError($onError);
    }

    $retry->command($command);

    return $retry->run();
}
