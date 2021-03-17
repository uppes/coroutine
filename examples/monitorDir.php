<?php

include 'vendor/autoload.php';

use function Async\Path\{file_delete, monitor_task, monitor_dir};

function repeat()
{
    $counter = 0;
    while (true) {
        $counter++;
        if ($counter == 500) {
            $counter = 0;
            \printf(".");
        }

        yield;
    }
}

function main()
{
    yield \away(\repeat());
    echo "Watching directory";

    $watchTask = yield monitor_task(function (?string $filename, int $events, int $status) {
        if ($status == 0) {
            echo \EOL . "Change detected in 'watch/temp': ";
            if ($events & \UV::RENAME)
                echo "renamed -";
            if ($events & \UV::CHANGE)
                echo "changed -";

            echo " filename: " . ($filename ? $filename : "") . \EOL;
        } elseif ($status < 0) {
            yield \kill_task();
        }
    });

    if (yield monitor_dir('watch/temp', $watchTask))
        echo " '" . __DIR__ . "/watch/temp' ";

    echo "for changes." . \EOL;

    yield \sleep_for(0.2);
    echo "To stop watching for changes, just 'delete' the directory!" . \EOL;

    yield \gather_wait([$watchTask], 0, false);

    yield file_delete('watch');
    yield \shutdown();
}

\coroutine_run(\main());
