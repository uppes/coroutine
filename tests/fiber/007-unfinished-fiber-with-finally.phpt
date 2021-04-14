--TEST--
Test unfinished fiber with finally block
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{
$fiber = new Fiber(function () {
    try {
        echo "fiber\n";
        echo yield Fiber::suspend();
        echo "after suspend\n";
    } catch (Throwable $exception) {
        echo "exit exception caught!\n";
    } finally {
        echo "finally\n";
    }

    echo "end of fiber should not be reached\n";
});

yield $fiber->start();

unset($fiber); // Destroy fiber object, executing finally block.

echo "done\n";

}

\coroutine_run(main());

--EXPECT--
fiber
done
finally
