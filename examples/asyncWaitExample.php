<?php
include 'vendor/autoload.php';

use Async\Coroutine\Call;
use Async\Coroutine\Scheduler;

function async(callable $asyncFunction) 
{
    return yield Call::coroutine(asyncAwait($asyncFunction));
}

function asyncAwait(callable $awaitableFunction, ...$args) 
{
    $tid = (yield Call::taskId());        
    return yield $awaitableFunction($tid, $args);
}

function asyncKill(int $tid) 
{
    return Call::killTask($tid); 
}

function await(callable $awaitedFunction) 
{     
    return async($awaitedFunction);
}

function childTask($tid) 
{
    while (true) {
        echo "Child task $tid still alive!\n";
        yield;
    }
};

function parentTask($tid) 
{
    $childTid = yield from await('childTask');

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield asyncKill($childTid);
    }
};

$scheduler = new Scheduler();
$scheduler->coroutine(asyncAwait('parentTask'));
$scheduler->run();
