--TEST--
Fatal error in a fiber with other active fibers
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{

$fiber1 = new Fiber(function() { yield Fiber::suspend(1); });

$fiber2 = new Fiber(function () {
    yield Fiber::suspend(2);
    trigger_error("Fatal error in fiber", E_USER_ERROR);
});

var_dump(yield $fiber1->start());
var_dump(yield $fiber2->start());
yield $fiber2->resume();

}

\coroutine_run(main());

--EXPECTF--
int(1)
int(2)

Fatal error: Fatal error in fiber in %S
