<?php

namespace Jralph\Retry;

use function call_user_func;

class Command
{
    /**
     * The callable to run for the command.
     *
     * @var callable
     */
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Run the commands handler.
     *
     * @param int $attempt
     * @return mixed
     */
    public function run(int $attempt)
    {
        return call_user_func($this->handler, $attempt);
    }
}
