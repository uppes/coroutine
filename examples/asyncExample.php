<?php
include 'vendor/autoload.php';

function childTask() 
{
    $tid = yield async_id();
    while (true) {
        echo "Child task $tid still alive!\n";
        yield;
    }
};

function parentTask() 
{
    $tid = yield \async_id();
    $childTid = yield \await('childTask');

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield \async_cancel($childTid);
    }
};

\coroutine_run(\parentTask());
