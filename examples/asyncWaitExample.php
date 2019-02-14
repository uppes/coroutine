<?php
include 'vendor/autoload.php';

use Async\Coroutine\Coroutine;

function childTask() 
{
    $tid = yield asyncId();
    while (true) {
        echo "Child task $tid still alive!\n";
        yield;
    }
};

function parentTask() 
{
    $tid = yield asyncId();
    $childTid = yield from await('childTask');

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield asyncRemove($childTid);
    }
};

$coroutine = new Coroutine();
$coroutine->addTask( parentTask() );
$coroutine->run();
