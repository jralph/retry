# PHP Retry [![Build Status](https://travis-ci.org/jralph/retry.svg)](https://travis-ci.org/jralph/retry) [![Latest Stable Version](https://poser.pugx.org/jralph/retry/v/stable)](https://packagist.org/packages/jralph/retry) [![Total Downloads](https://poser.pugx.org/jralph/retry/downloads.svg)](https://packagist.org/packages/jralph/retry) [![Latest Unstable Version](https://poser.pugx.org/jralph/retry/v/unstable.svg)](https://packagist.org/packages/jralph/retry) [![License](https://poser.pugx.org/jralph/retry/license.svg)](https://packagist.org/packages/jralph/retry)

A simple library to retry commands in php.


## Installation

This package is available through composer.

    composer require jralph/retry
   
## Basic Use

The library includes a simple helper function for ease of use.

*Note: On a failure to succeed in running the command, a `Jralph\Retry\RetryException` will be thrown.*

```php
mixed retry (int|callable $attempts , callable $command [, callable $onError = null])
```

### Parameters

- `$attempts:` The number of times to attempt a command.
    - If a `callable` is provided, the retry will continue until this closure returns true.
- `$command:` The command to run each time.
- `$onError:` An optional callback to run each time the `$command` fails.

### Example

```php
<?php

use function Jralph\Retry\retry;

$result = retry(3, function (int $attempt) {
    // Throwing an error as an example....first 2 attempts will fail.
    if ($attempt < 2) {
        throw new Exception('Just throwing an error as an example!');
    }
    
    return 'Hello World!';
});

// Outputs 'Hello World!'
echo $result;
```

## Advanced Use / Object Use

If you want more flexibility over the retry tool, you can use the `Retry` object directly and ignore the helper function.

The `Retry` object is fully chainable to make things simple.

```php
<?php

use Jralph\Retry\Retry;

$retry = new Retry(function (int $attempt) {
    // Throwing an error as an example....first 2 attempts will fail.
    if ($attempt < 2) {
        throw new Exception('Just throwing an error as an example!');
    }
    
    return 'Hello World!';
});

$result = $retry->attempts(3)->run();

// Outputs 'Hello World!'
echo $result;
```

### Available Methods

- `new Retry(callable|Command $command);` The closure or Command object to run as the command.
- `$retry->attempts(int $attempts);` The maximum number of times to attempt the command.
- `$retry->wait(int $seconds);` The number of seconds to wait between attempts.
- `$retry->once();` Alias for `$retry->attempts(1);`
- `$retry->twice();` Alias for `$retry->attempts(2);`
- `$retry->thrice();` Alias for `$retry->attempts(3);`
- `$retry->forever();` Alias for `$retry->attempts(0);` *Note: Be careful wit this!*
- `$retry->until(callable $until);` Retry until the result of `$until` returns true. *Note: Works well with `$retry->forever();`*
    - Accepts `$attempt` as the first parameter, giving the current number of attempts.
    - Accepts `$response` as the second parameter, giving the response of the last attempt.
- `$retry->onlyIf(callable $onlyIf);` Retry only if the `$onlyIf` returns true.
    - Accepts `$attempt` as the first parameter, giving the current number of attempts.
    - Accepts `$response` as the second parameter, giving the response of the last attempt.
- `$retry->onError(callable $onError);` A callback to run each time the retry fails.
    - Accepts `$attempt` as the first parameter, giving the current number of attempts.
    - Accepts `$response` as the second parameter, giving the response of the last attempt.
- `$retry->run();` Run the command using the specified setup.

## Change Log

- `1.2.0` Updated to php 7.1+ and deprecated `command` method in favour of padding a command object into the constructor.
- `1.1.1` Changed all methods using `\Closure` to use `callable` instead.
- `1.1.0` Added `wait(int $seconds);` method.
