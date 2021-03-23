<?php

include 'vendor/autoload.php';

use Async\Coroutine\Fiber;

function main()
{
    $fiber = new Fiber(function () {
        $value = yield Fiber::suspend('fiber');
        echo "Value used to resume fiber: ", $value, "\n";
    });

    $value = yield $fiber->start();

    echo "Value from fiber suspending: ", $value, "\n";

    yield $fiber->resume('test');
}

\coroutine_run(main());
