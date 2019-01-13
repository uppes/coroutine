<?php
include 'vendor/autoload.php';

use Async\Task\Syscall;
use Async\Task\Scheduler;

function task($max) {
    $tid = (yield Syscall::getTaskId()); // <-- here's the syscall!
    for ($i = 1; $i <= $max; ++$i) {
        echo "This is task $tid iteration $i.\n";
        yield;
    }
}

$scheduler = new Scheduler;

$scheduler->coroutine(task(10));
$scheduler->coroutine(task(5));

$scheduler->run();
