--TEST--
Silence operator does not leak out of fiber
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function suspend_with_warnings() {
    @trigger_error("Warning A", E_USER_WARNING); // Should be silenced.
    yield Fiber::suspend();
    @trigger_error("Warning B", E_USER_WARNING); // Should be silenced.
}

function main()
{

$fiber = new Fiber(function () {
    yield @suspend_with_warnings();
});

yield $fiber->start();

trigger_error("Warning C", E_USER_WARNING);

yield $fiber->resume();

trigger_error("Warning D", E_USER_WARNING);

}

\coroutine_run(main());

--EXPECTF--
Warning: Warning C in %S

Warning: Warning D in %S
