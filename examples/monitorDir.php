<?php

include 'vendor/autoload.php';

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
    echo "Watching directory ";

    $watchTask = yield \monitor_task(function (UVFsEvent $handle, ?string $filename, int $events, int $status) {
        if ($status == 0) {
            echo \EOL . "Change detected in 'watch/temp': ";
            if ($events & \UV::RENAME)
                echo "renamed -";
            if ($events & \UV::CHANGE)
                echo "changed -";

            echo " filename: " . ($filename ? $filename : "") . \EOL;
        } elseif ($status < 0) {
            \uv_close($handle);
            yield \cancel_task(yield \get_task());
        }
    });

    if (yield \monitor_dir('watch/temp', $watchTask))
        echo "'watch/temp'";

    echo " for changes." . \EOL;
    yield \gather_wait([$watchTask], 0, false);

    yield \file_delete('watch');
    yield \shutdown();
}

\coroutine_run(\main());
