<?php

namespace spec\Jralph\Retry;

use Exception;
use Jralph\Retry\Command;
use Jralph\Retry\Retry;
use Jralph\Retry\RetryException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RetrySpec extends ObjectBehavior
{
    function let(Command $command)
    {
        $this->beConstructedWith($command);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Retry::class);
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

    function it_returns_1_for_successful_first_try(Command $command)
    {
        $command->run(1)->shouldBeCalledTimes(1)->willReturn(1);

        $this->run()->shouldReturn(1);
    }

    function it_returns_2_for_2_attempts_and_success(Command $command)
    {
        $command->run(1)->shouldBeCalledTimes(1)->willThrow(new Exception());
        $command->run(2)->shouldBeCalledTimes(1)->willReturn(2);

        $this->attempts(2)->run()->shouldReturn(2);
    }

    function it_returns_27_for_27_attemepts_and_success(Command $command)
    {
        $command->run(Argument::not(27))->shouldBeCalledTimes(26)->willThrow(new Exception());
        $command->run(27)->shouldBeCalledTimes(1)->willReturn(27);

        $this->attempts(27)->run()->shouldReturn(27);
    }

    function it_throws_retry_exception_if_max_attempts_reached_and_failed(Command $command)
    {
        $command->run(Argument::type('integer'))->shouldBeCalledTimes(2)->willThrow(new Exception());

        $this->attempts(2);

        $this->shouldThrow(RetryException::class)->duringRun();
    }

    function it_should_retry_based_on_condition(Command $command)
    {
        $command->run(1)->shouldBeCalledTimes(1)->willReturn('hello');
        $command->run(2)->shouldBeCalledTimes(1)->willReturn('world');

        $this->attempts(2)->onlyIf(function (int $attempts, $response) {
            return $response == 'hello';
        })->run()->shouldReturn('world');
    }

    function it_should_retry_based_on_condition_callable(Command $command)
    {
        $class = new class {
            public function onlyIf(int $attempts, $response) : bool
            {
                return $response == 'hello';
            }
        };

        $command->run(1)->shouldBeCalledTimes(1)->willReturn('hello');
        $command->run(2)->shouldBeCalledTimes(1)->willReturn('world');

        $this->attempts(2)->onlyIf([$class, 'onlyIf'])->run()->shouldReturn('world');
    }

    function it_should_retry_once_with_once(Command $command)
    {
        $command->run(1)->shouldBeCalledTimes(1)->willReturn(1);

        $this->once()->run()->shouldReturn(1);
    }

    function it_should_retry_twice_with_twice(Command $command)
    {
        $command->run(1)->shouldBeCalledTimes(1)->willThrow(new Exception());
        $command->run(2)->shouldBeCalledTimes(1)->willReturn(2);

        $this->twice()->run()->shouldReturn(2);
    }

    function it_should_retry_thrice_with_thrice(Command $command)
    {
        $command->run(Argument::not(3))->shouldBeCalledTimes(2)->willThrow(new Exception());
        $command->run(3)->shouldBeCalledTimes(1)->willReturn(3);

        $this->thrice()->run()->shouldReturn(3);
    }

    function it_should_retry_until_success(Command $command)
    {
        $command->run(Argument::not(3))->shouldBeCalledTimes(2)->willThrow(new Exception());
        $command->run(3)->shouldBeCalledTimes(1)->willReturn(3);

        $this->attempts(4)->run()->shouldReturn(3);
    }

    function it_should_retry_until_closure(Command $command)
    {
        $command->run(Argument::type('integer'))->shouldBeCalledTimes(2)->willThrow(new Exception());

        $this->forever()->until(function (int $attempts, $response) {
            return $attempts === 2;
        });

        $this->shouldThrow(RetryException::class)->duringRun();
    }

    function it_should_retry_until_callable(Command $command)
    {
        $command->run(Argument::type('integer'))->shouldBeCalledTimes(2)->willThrow(new Exception());

        $class = new class {
            public function until(int $attempts, $response)
            {
                return $attempts === 2;
            }
        };

        $this->forever()->until([$class, 'until']);

        $this->shouldThrow(RetryException::class)->duringRun();
    }

    function it_should_run_on_error(Command $command)
    {
        $command->run(Argument::type('integer'))->shouldBeCalledTimes(1)->willThrow(new Exception());

        $errorException = new class extends \Exception {};

        $this->once()->onError(function (int $attempts, $response) use ($errorException) {
            throw $errorException;
        });

        $this->shouldThrow($errorException)->duringRun();
    }

    function it_should_run_on_error_with_callable(Command $command)
    {
        $command->run(Argument::type('integer'))->shouldBeCalledTimes(1)->willThrow(new Exception());

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

        $this->once()->onError([$class, 'onError']);

        $this->shouldThrow($errorException)->duringRun();
    }

    function it_returns_expected_result_using_wait(Command $command)
    {
        $command->run(1)->shouldBeCalledTimes(1)->willReturn(1);

        $this->wait(1)->run()->shouldReturn(1);
    }
}
