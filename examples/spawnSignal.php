<?php

include 'vendor/autoload.php';

function repeat()
{
    while (true) {
        \printf(".");
        yield;
    }
}

function main()
{
    //yield \away(\repeat());
    $signal = yield \signal_task(\SIGKILL, function ($signal) {
        echo "The process has been terminated with 'SIGKILL - " . $signal . "' signal!" . \EOL . \EOL;
        yield \shutdown();
    });

    $process = yield \spawn_signal(function () {
        echo "hello ";
        return 'world.';
    }, \SIGKILL, $signal);


    $result = yield \gather($process);
    var_dump($process);
    echo "Process task id: " . $process . " returned:" . $result[$process] . \EOL;
    yield \shutdown();
}

\coroutine_run(\main());
