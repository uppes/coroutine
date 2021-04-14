--TEST--
Fiber::getReturn() in unfinished fiber
--SKIPIF--
<?php if (!((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{

$fiber = new Fiber(function() { yield Fiber::suspend(1);});

var_dump(yield $fiber->start());

$fiber->getReturn();

}

\coroutine_run(main());

--EXPECTF--
int(1)

Fatal error: Uncaught Async\FiberError: Cannot get fiber return value: The fiber has not returned in %S
Stack trace:
#0 %S
#1 [internal function]: main()
#2 %S
#3 [internal function]: Async\Coroutine::create(%S
#4 %S
#5 %S
#6 %S
#7 %S
#8 %S
#9 {main}
  thrown in %S
