--TEST--
Throw into non-running fiber
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{

$fiber = new Fiber(function() { return null; });

yield $fiber->throw(new Exception('test'));

}

\coroutine_run(main());

--EXPECTF--
Fatal error: Uncaught Async\Coroutine\FiberError: Cannot resume a fiber that is not suspended in %S
Stack trace:
#0 [internal function]: Async\Coroutine\Fiber->throw(Object(Exception))
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
