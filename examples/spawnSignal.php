<?php

include 'vendor/autoload.php';

use function Async\Worker\{signal_task, spawn_signal, spawn_kill};

function repeat()
{
    $counter = 0;
    while (true) {
        $counter++;
        if ($counter === 20) {
            $counter = 0;
            \printf(".");
        }
        yield;
    }
}

function main()
{
    yield \away(\repeat());
    $signal = yield signal_task(\SIGKILL, function ($signal) {
        echo "the process has been terminated with 'SIGKILL - " . $signal . "' signal!" . \EOL;
        yield;
    });

    $process = yield spawn_signal(function () {
        echo "Hello, ";

        return 'world.';
    }, \SIGKILL, $signal, 0, true);

    $kill = yield \away(function () use ($process) {
        yield \sleep_for(0.90);
        $bool = yield spawn_kill($process);
        return $bool;
    });

    $result = yield \gather_wait([$process, $kill], 0, false);
    echo \EOL . "The process task with id: " . $process . " returned exception of: " .  \get_class($result[$process]) . \EOL;
    echo "The kill task with id: " . $kill . " returned bool: " .  $result[$kill] . \EOL;
    yield \shutdown();
}

\coroutine_run(\main());
