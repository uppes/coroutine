--TEST--
Test unfinished fiber with nested try/catch blocks
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{
$fiber = new Fiber(function () {
    try {
        try {
            try {
                echo "fiber\n";
                echo yield Fiber::suspend();
                echo "after await\n";
            } catch (Throwable $exception) {
                echo "inner exit exception caught!\n";
            }
        } catch (Throwable $exception) {
            echo "exit exception caught!\n";
        } finally {
            echo "inner finally\n";
        }
    } finally {
        echo "outer finally\n";
    }

    echo "unreached\n";

    try {
        echo yield Fiber::suspend();
    } finally {
        echo "unreached\n";
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
inner finally
outer finally
