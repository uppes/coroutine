--TEST--
Test unfinished fiber with suspend in finally
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{
$fiber = new Fiber(function () {
    try {
        try {
            echo "fiber\n";
            return new \stdClass;
        } finally {
            echo "inner finally\n";
            yield Fiber::suspend();
            echo "after await\n";
        }
    } catch (Throwable $exception) {
        echo "exit exception caught!\n";
    } finally {
        echo "outer finally\n";
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
inner finally
done
outer finally
