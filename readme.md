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
use Jralph\Retry\Command;

$retry = new Retry(new Command(function (int $attempt) {
    // Throwing an error as an example....first 2 attempts will fail.
    if ($attempt < 2) {
        throw new Exception('Just throwing an error as an example!');
    }
    
    return 'Hello World!';
}));

$result = $retry->attempts(3)->run();

// Outputs 'Hello World!'
echo $result;
```

### Available Methods

- `new Retry(Command $command);` The Command object to run as the command.
- `$retry->attempts(int $attempts);` The maximum number of times to attempt the command. Note, an attempt count of 0 will run for ever!
- `$retry->wait(int $seconds);` The number of seconds to wait between attempts.
- `$retry->until(callable $until);` Retry until the result of `$until` returns true. *Note: Works well with `$retry->attempts(0);`*
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

- `2.0.0`
  - Removing of deprecated methods.
  - Removal of once, twice, thrice, forever methods in favour of using `attempts(int $attempts)`.
  - Constructor only accepts `Command` objects.
- `1.2.0` 
  - Updated to php 7.1+.
  - Dprecated `command` method in favour of passing a command object into the constructor.
- `1.1.1`
  - Changed all methods using `\Closure` to use `callable` instead.
- `1.1.0`
  - Added `wait(int $seconds);` method.
