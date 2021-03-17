<?php

include 'vendor/autoload.php';

use function Async\Path\{file_close, file_fdatasync, file_open, file_write};
use function Async\Worker\spawn_await;

function repeat()
{
    $counter = 0;
    while (true) {
        $counter++;
        \printf(".");
        yield;
    }
}

// Event loop for parallel tasks
function main()
{
    $command = 'unlink';
    $parameters = "./tmp";

    yield \away(\repeat());

    $fd = yield file_open("./tmp", 'a', \UV::S_IRWXU | \UV::S_IRUSR);
    yield file_write($fd, "hello", 0);
    yield file_fdatasync($fd);
    yield file_close($fd);

    echo "\nThe task returned:\n";
    $result = yield spawn_await(function () use ($command, $parameters) {
        return $command($parameters);
    });
    \var_dump($result);

    yield \shutdown();
};

\coroutine_run(\main());
