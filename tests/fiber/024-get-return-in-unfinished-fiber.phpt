--TEST--
Fiber::getReturn() in unfinished fiber
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{

$fiber = new Fiber(function() { yield Fiber::suspend(1);});

var_dump(yield $fiber->start());

$fiber->getReturn();

}

\coroutine_run(main());

--EXPECTF--
int(1)

Fatal error: Uncaught Async\Coroutine\FiberError: Cannot get fiber return value: The fiber has not returned in %S
Stack trace:
#0 %S
#1 [internal function]: main()
#2 %S
#3 [internal function]: Async\Coroutine\Coroutine::create(%S
#4 %S
#5 %S
#6 %S
#7 %S
#8 %S
#9 %S
