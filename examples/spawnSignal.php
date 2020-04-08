<?php

include 'vendor/autoload.php';

function repeat()
{
    $counter = 0;
    while (true) {
        $counter++;
        if ($counter === 20000) {
            $counter = 0;
            \printf(".");
        }
        yield;
    }
}

function main()
{
    //yield \away(\repeat());
    $signal = yield \signal_task(\SIGKILL, function ($signal) {
        echo "the process has been terminated with 'SIGKILL - " . $signal . "' signal!" . \EOL . \EOL;
        yield;
    });

    $process = yield \spawn_signal(function () {
        echo "Hello, ";

        \returning(1550195);
        return 'world.';
    }, \SIGKILL, $signal, 0, true);

    $kill = yield \away(function () use ($process) {
        yield \sleep_for(0.90);
        $bool = yield \spawn_kill($process);
        return $bool;
    });

    $result = yield \gather_wait([$process, $kill], 0, false);
    echo "The process task with id: " . $process . " returned:" . $result[$process] . \EOL;
    echo "The kill task with id: " . $kill . " returned: " . $result[$kill] . \EOL;
    yield \shutdown();
}

\coroutine_run(\main());
