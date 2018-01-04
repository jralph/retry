<?php

namespace spec\Jralph\Retry;

use Jralph\Retry\Command;
use Jralph\Retry\Retry;
use Jralph\Retry\RetryException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RetrySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Retry::class);
    }

    function it_sets_command_as_callable()
    {
        $this->command(function () {});
    }

    function it_sets_command_as_command_object()
    {
        $this->command(new Command(function () {}));
    }

    function it_returns_self_from_command()
    {
        $this->command(function () {})->shouldHaveType(Retry::class);
    }

    function it_should_error_if_command_is_not_closure()
    {
        $this->shouldThrow(\Throwable::class)->duringCommand('test');
    }

    function it_sets_number_of_attempts()
    {
        $this->attempts(3);
    }

    function it_sets_wait_time()
    {
        $this->wait(1);
    }

    function it_returns_self_from_wait()
    {
        $this->wait(1)->shouldHaveType(Retry::class);
    }

    function it_accepts_closure_as_until()
    {
        $this->until(function() {});
    }

    function it_accepts_callable_as_until()
    {
        $class = new class {
            public function until() {}
        };

        $this->until([$class, 'until']);
    }

    function it_accepts_closure_as_only_if()
    {
        $this->onlyIf(function() {});
    }

    function it_accepts_callable_as_only_if()
    {
        $class = new class {
            public function onlyIf() {}
        };

        $this->onlyIf([$class, 'onlyIf']);
    }

    function it_accepts_closure_as_on_error()
    {
        $this->onError(function() {});
    }

    function it_accepts_callable_as_on_error()
    {
        $class = new class {
            public function onError() {}
        };

        $this->onError([$class, 'onError']);
    }

    function it_should_error_if_attempts_is_not_numeric()
    {
        $this->shouldThrow(\Throwable::class)->duringAttempts('nan');
    }

    function it_returns_self_from_attempts()
    {
        $this->attempts(3)->shouldHaveType(Retry::class);
    }

    function it_accepts_closure_as_command()
    {
        $this->command(function() {});
    }

    function it_accepts_callable_as_command()
    {
        $class = new class {
            public function run()
            {

            }
        };

        $this->command([$class, 'run']);
    }

    function it_returns_1_for_successful_first_try_with_callable()
    {
        $class = new class {
            public function run(int $attempt)
            {
                return $attempt;
            }
        };

        $this->command([$class, 'run'])->run()->shouldReturn(1);
    }

    function it_returns_1_for_successful_first_try_with_command_class()
    {
        $command = new Command(function (int $attempt) {
            return $attempt;
        });

        $this->command($command)->run()->shouldReturn(1);
    }

    function it_returns_1_for_successful_first_try()
    {
        $this->command(function (int $attempt) { return $attempt; })->run()->shouldReturn(1);
    }

    function it_returns_2_for_2_attemepts_and_success()
    {
        $this->command(function (int $attempt) {
            if ($attempt !== 2) {
                throw new \Exception;
            }

            return $attempt;
        })->attempts(2)->run()->shouldReturn(2);
    }

    function it_returns_27_for_27_attemepts_and_success()
    {
        $this->command(function (int $attempt) {
            if ($attempt !== 27) {
                throw new \Exception;
            }

            return $attempt;
        })->attempts(27)->run()->shouldReturn(27);
    }

    function it_throws_retry_exception_if_max_attempts_reached_and_failed()
    {
        $this->command(function () {
            throw new \Exception;
        });

        $this->attempts(2);

        $this->shouldThrow(RetryException::class)->duringRun();
    }

    function it_should_retry_based_on_condition()
    {
        $this->command(function (int $attempt) {
            if ($attempt !== 2) {
                return 'hello';
            }

            return 'world';
        })->attempts(2)->onlyIf(function (int $attempts, $response) {
            return $response == 'hello';
        })->run()->shouldReturn('world');
    }

    function it_should_retry_based_on_condition_callable()
    {
        $class = new class {
            public function onlyIf(int $attempts, $response) : bool
            {
                return $response == 'hello';
            }
        };

        $this->command(function (int $attempt) {
            if ($attempt !== 2) {
                return 'hello';
            }

            return 'world';
        })->attempts(2)->onlyIf([$class, 'onlyIf'])->run()->shouldReturn('world');
    }

    function it_should_retry_once_with_once()
    {
        $this->command(function (int $attempt) {
            return $attempt;
        })->once()->run()->shouldReturn(1);
    }

    function it_should_retry_twice_with_twice()
    {
        $this->command(function (int $attempt) {
            if ($attempt < 2) {
                throw new \Exception;
            }

            return $attempt;
        })->twice()->run()->shouldReturn(2);
    }

    function it_should_retry_thrice_with_thrice()
    {
        $this->command(function (int $attempt) {
            if ($attempt < 3) {
                throw new \Exception;
            }

            return $attempt;
        })->thrice()->run()->shouldReturn(3);
    }

    function it_should_retry_until_success()
    {
        $this->command(function (int $attempt) {
            if ($attempt < 3) {
                throw new \Exception;
            }

            return $attempt;
        })->attempts(4)->run()->shouldReturn(3);
    }

    function it_should_retry_until_closure()
    {
        $this->command(function (int $attempt) {
            throw new \Exception;
        })->forever()->until(function (int $attempts, $response) {
            return $attempts === 2;
        });

        $this->shouldThrow(RetryException::class)->duringRun();
    }

    function it_should_retry_until_callable()
    {
        $class = new class {
            public function until(int $attempts, $response)
            {
                return $attempts === 2;
            }
        };

        $this->command(function (int $attempt) {
            throw new \Exception;
        })->forever()->until([$class, 'until']);

        $this->shouldThrow(RetryException::class)->duringRun();
    }

    function it_should_run_on_error()
    {
        $errorException = new class extends \Exception {};

        $this->command(function (int $attempt) {
            throw new \Exception;
        })->once()->onError(function (int $attempts, $response) use ($errorException) {
            throw $errorException;
        });

        $this->shouldThrow($errorException)->duringRun();
    }

    function it_should_run_on_error_with_callable()
    {
        $errorException = new class extends \Exception {};

        $class = new class($errorException) {
            protected $errorException;

            public function __construct($errorException)
            {
                $this->errorException = $errorException;
            }

            public function onError(int $attempts, $response)
            {
                throw $this->errorException;
            }
        };

        $this->command(function (int $attempt) {
            throw new \Exception;
        })->once()->onError([$class, 'onError']);

        $this->shouldThrow($errorException)->duringRun();
    }

    function it_returns_expected_result_using_wait()
    {
        $this->command(function (int $attempt) {
            return $attempt;
        })->wait(1)->run()->shouldReturn(1);  
    }
}
