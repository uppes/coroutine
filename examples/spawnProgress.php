<?php

include 'vendor/autoload.php';

use Async\Spawn\Channeled;

use function Async\Worker\progress_task;
use function Async\Worker\spawn_progress;

function repeat()
{
    $counter = 0;
    while (true) {
        $counter++;
        if ($counter == 200) {
            $counter = 0;
            \printf(".");
        }

        yield;
    }
}

function main()
{
    $ipc = new Channeled();

    echo "Let's play, ";

    yield \away(\repeat());
    $pTask = yield progress_task(function ($type, $data) use ($ipc) {
        echo $ipc->recv();
        if ('ping' === $data) {
            $ipc->send('pang' . \PHP_EOL);
        } elseif (!$ipc->isClosed()) {
            $ipc->send('pong. ' . \PHP_EOL);
            $ipc->close();
        }
    });

    $process = yield spawn_progress(
        function (Channeled $channel) {
            $channel->write('ping');
            echo $channel->read();
            echo $channel->read();

            return 'The game!';
        },
        $ipc,
        $pTask
    );

    $result = yield \gather($process);
    echo \EOL . "I like, " . $result[$process] . \EOL;
    yield \shutdown();
}

\coroutine_run(\main());
