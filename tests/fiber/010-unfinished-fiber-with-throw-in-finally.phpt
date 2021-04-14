--TEST--
Test unfinished fiber with suspend in finally
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;
use Async\FiberError;

function main()
{
$fiber = new Fiber(function () {
    try {
        try {
            try {
                echo "fiber\n";
                echo yield Fiber::suspend();
                echo "after await\n";
            } catch (\Throwable $exception) {
                echo "inner exit exception caught!\n";
            }
        } catch (\Throwable $exception) {
            echo "exit exception caught!\n";
        } finally {
            echo "inner finally\n";
            throw new \Exception("finally exception");
        }
    } catch (\Exception $exception) {
        echo $exception->getMessage(), "\n";
        // echo \get_class($exception->getPrevious()), "\n";
    } finally {
        echo "outer finally\n";
    }

    try {
        echo yield Fiber::suspend();
    } catch (FiberError $exception) {
        echo $exception->getMessage(), "\n";
    }
});

yield $fiber->start();

unset($fiber); // Destroy fiber object, executing finally block.

echo "done\n";
}

\coroutine_run(main());

--EXPECTF--
fiber
done
inner finally
finally exception
outer finally

Fatal error: Uncaught Error: Cannot yield from finally in a force-closed generator in %S
Stack trace:
#0 %S
#1 %S
#2 [internal function]: Async\Fiber->__destruct()
#3 {main}
  thrown in %S
