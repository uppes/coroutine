--TEST--
Resume terminated fiber
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{

$fiber = new Fiber(function() { return null;});

yield $fiber->start();

yield $fiber->resume();

}

\coroutine_run(main());

--EXPECTF--
Fatal error: Uncaught Async\Coroutine\FiberError: Cannot resume a fiber that is not suspended in %S
Stack trace:
#0 %S
#1 %S
#2 [internal function]: Async\Coroutine\Coroutine::create(Object(Generator))
#3 %S
#4 %S
#5 %S
#6 %S
#7 %S
#8 %S
#9 {main}
  thrown in %S
