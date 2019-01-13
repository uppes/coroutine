<?php
include 'vendor/autoload.php';

use Async\Task\Scheduler;

function task1() {
    for ($i = 1; $i <= 10; ++$i) {
        echo "This is task 1 iteration $i.\n";
        yield;
    }
}

function task2() {
    for ($i = 1; $i <= 5; ++$i) {
        echo "This is task 2 iteration $i.\n";
        yield;
    }
}

$scheduler = new Scheduler;

$scheduler->coroutine(task1());
$scheduler->coroutine(task2());

$scheduler->run();
