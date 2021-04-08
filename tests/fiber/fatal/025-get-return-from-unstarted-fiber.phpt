--TEST--
Fiber::getReturn() from unstarted fiber
--SKIPIF--
<?php if (!((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{

$fiber = new Fiber(function() {yield Fiber::suspend(1);});

$fiber->getReturn();

}

\coroutine_run(main());

--EXPECTF--
Fatal error: Uncaught Async\Coroutine\FiberError: Cannot get fiber return value: The fiber has not been started in %S
Stack trace:
#0 %S
#1 %S
#2 {main}
  thrown in %S
