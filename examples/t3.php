<?php
include 'vendor/autoload.php';

use Async\Task\Syscall;
use Async\Task\Scheduler;

function childTask() {
    $tid = (yield Syscall::getTaskId());
    while (true) {
        echo "Child task $tid still alive!\n";
        yield;
    }
}

function task() {
    $tid = (yield Syscall::getTaskId());
    $childTid = (yield Syscall::coroutine(childTask()));

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield Syscall::killTask($childTid);
    }
}

$scheduler = new Scheduler;
$scheduler->coroutine(task());
$scheduler->run();
