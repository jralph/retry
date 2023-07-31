<?php

namespace Jralph\Retry;

/**
 * Retry a given callable a given number of times on failure.
 *
 * @param int|callable $attempts The number of attempts to try or a closure that should return true when to stop.
 * @param callable $command
 * @param ?callable $onError
 * @return int
 * @throws RetryException
 */
function retry(int|callable $attempts, callable $command, callable $onError = null): int
{
    $retry = new Retry(new Command($command));

    if (is_numeric($attempts)) {
        $retry->attempts($attempts);
    } else if (is_callable($attempts)) {
        $retry->attempts(0)->until($attempts);
    }

    if ($onError) {
        $retry->onError($onError);
    }

    return $retry->run();
}
