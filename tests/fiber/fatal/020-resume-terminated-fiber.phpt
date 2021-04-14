--TEST--
Resume terminated fiber
--SKIPIF--
<?php if (!((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{

$fiber = new Fiber(function() { return null;});

yield $fiber->start();

yield $fiber->resume();

}

\coroutine_run(main());

--EXPECTF--
Fatal error: Uncaught Async\FiberError: Cannot resume a fiber that is not suspended in %S
Stack trace:
#0 %S
#1 %S
#2 [internal function]: Async\Coroutine::create(%S
#3 %S
#4 %S
#5 %S
#6 %S
#7 %S
#8 {main}
  thrown in %S
