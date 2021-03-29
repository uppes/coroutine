--TEST--
Exit from fiber
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{

$fiber = new Fiber(function () {
    yield Fiber::suspend();
    echo "resumed\n";
    exit();
});

yield $fiber->start();

yield $fiber->resume();

echo "unreachable\n";
}

\coroutine_run(main());

--EXPECT--
resumed
