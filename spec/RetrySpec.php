<?php

namespace spec\Jralph\Retry;

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

    function it_returns_self_from_command()
    {
        $this->command(function () {})->shouldHaveType(Retry::class);
    }

    function it_should_error_if_command_is_not_closure()
    {
        $this->shouldThrow(\Throwable::class)->duringCommand('test');
    }

    function it_sets_number_of_retries()
    {
        $this->retries(3);
    }

    function it_should_error_if_retries_is_not_numeric()
    {
        $this->shouldThrow(\Throwable::class)->duringRetries('nan');
    }

    function it_returns_self_from_retries()
    {
        $this->retries(3)->shouldHaveType(Retry::class);
    }

    function it_returns_1_for_successful_first_try()
    {
        $this->command(function () {})->run()->shouldReturn(1);
    }

    function it_returns_2_for_2_attemepts_and_success()
    {
        $this->command(function (int $attempt) {
            if ($attempt !== 2) {
                throw new \Exception;
            }
        })->retries(2)->run()->shouldReturn(2);
    }

    function it_returns_27_for_27_attemepts_and_success()
    {
        $this->command(function (int $attempt) {
            if ($attempt !== 27) {
                throw new \Exception;
            }
        })->retries(27)->run()->shouldReturn(27);
    }

    function it_throws_retry_exception_if_max_retries_reached_and_failed()
    {
        $this->command(function () {
            throw new \Exception;
        });

        $this->retries(2);

        $this->shouldThrow(RetryException::class)->duringRun();
    }

    function it_should_retry_based_on_condition()
    {
        $this->command(function (int $attempt) {
            if ($attempt !== 2) {
                return 'hello';
            }

            return 'world';
        })->retries(2)->onlyIf(function ($response) {
            return $response == 'hello';
        })->run()->shouldReturn(2);
    }

    function it_should_retry_once_with_once()
    {
        $this->command(function (int $attempt) {

        })->once()->run()->shouldReturn(1);
    }

    function it_should_retry_twice_with_twice()
    {
        $this->command(function (int $attempt) {
            if ($attempt < 2) {
                throw new \Exception;
            }
        })->twice()->run()->shouldReturn(2);
    }

    function it_should_retry_thrice_with_thrice()
    {
        $this->command(function (int $attempt) {
            if ($attempt < 3) {
                throw new \Exception;
            }
        })->thrice()->run()->shouldReturn(3);
    }

    function it_should_retry_until_success()
    {
        $this->command(function (int $attempt) {
            if ($attempt < 3) {
                throw new \Exception;
            }
        })->retries(4)->run()->shouldReturn(3);
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
}
