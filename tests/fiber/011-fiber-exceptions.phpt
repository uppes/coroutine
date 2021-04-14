--TEST--
Fiber exception classes cannot be constructed in user code
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;
use Async\FiberError;
use Async\FiberExit;

function main()
{

try {
    throw new FiberError('The "FiberError" class is reserved for internal use and cannot be manually instantiated');
} catch (\Throwable $exception) {
    echo $exception->getMessage(), "\n";
}

try {
    throw new FiberExit('The "FiberExit" class is reserved for internal use and cannot be manually instantiated');
} catch (\Throwable $exception) {
    echo $exception->getMessage(), "\n";
}
}

\coroutine_run(main());

--EXPECT--
The "FiberError" class is reserved for internal use and cannot be manually instantiated
The "FiberExit" class is reserved for internal use and cannot be manually instantiated
