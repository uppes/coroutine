<?php
include 'vendor/autoload.php';

use Async\Coroutine\Kernel;

\async('childTask', function ($av = null)
{
    $tid = yield \async_id();
    while (true) {
        echo "Child task $tid still alive! $av\n";
        yield;
    }
});

function parentTask() 
{
    $tid = yield \async_id();
    $childTid = yield \await('childTask', 'using async() function');

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield \async_cancel($childTid);
    }
};

\coroutine_run(\parentTask());
