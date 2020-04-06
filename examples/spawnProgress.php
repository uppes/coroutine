<?php

include 'vendor/autoload.php';

use Async\Spawn\Channeled;
use Async\Spawn\ChanneledInterface;

function main()
{
    $ipc = new Channeled();

    echo "Let's play, ";

    $pTask = yield \progress_task(function ($type, $data) use ($ipc) {
        if ('ping' === $data) {
            $ipc->send('pang' . \PHP_EOL);
        } elseif (!$ipc->isClosed()) {
            $ipc->send('pong. ' . \PHP_EOL)
                ->close();
        }
    });

    $process = yield \spawn_progress(
        function (ChanneledInterface $channel) {
            $channel->write('ping');
            echo $channel->read();
            echo $channel->read();

            returning();
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
