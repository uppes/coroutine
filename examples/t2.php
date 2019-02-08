<?php
include 'vendor/autoload.php';

use Async\Coroutine\Syscall;
use Async\Coroutine\Scheduler;

$task = null;

function task($max) {
    global $task;
    $tid = (yield Syscall::getTaskId()); // <-- here's the syscall!
    for ($i = 1; $i <= $max; ++$i) {
        $task .= "This is task $tid iteration $i.\n";
        yield;
    }
}

$scheduler = new Scheduler;

$scheduler->coroutine(task(10));
$scheduler->coroutine(task(5));

$scheduler->run();

echo $task;
