--TEST--
Fatal error in new fiber
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{

$fiber = new Fiber(function () {
    trigger_error("Fatal error in fiber", E_USER_ERROR);
});

yield $fiber->start();
}

\coroutine_run(main());

--EXPECTF--
Fatal error: Fatal error in fiber in %S
