--TEST--
Fiber::getReturn() after a fiber throws
--SKIPIF--
<?php if (!((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{

$fiber = new Fiber(function() { throw new \Exception('test');});

try {
    yield $fiber->start();
} catch (Exception $exception) {
    echo $exception->getMessage(), "\n";
}

$fiber->getReturn();

}

\coroutine_run(main());

--EXPECTF--
test

Fatal error: Uncaught Async\FiberError: Cannot get fiber return value: The fiber has not returned in %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 [internal function]: Async\Coroutine::create(%S
#4 %S
#5 %S
#6 %S
#7 %S
#8 %S
#9 {main}
  thrown in %S
