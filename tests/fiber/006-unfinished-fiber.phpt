--TEST--
Test unfinished fiber
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{
$fiber = new Fiber(function () {
    try {
        echo "fiber\n";
        echo yield Fiber::suspend();
        echo "after suspend\n";
    } catch (Throwable $exception) {
        echo "exit exception caught!\n";
    }

    echo "end of fiber should not be reached\n";
});

yield $fiber->start();

echo "done\n";
}

\coroutine_run(main());

--EXPECT--
fiber
done
