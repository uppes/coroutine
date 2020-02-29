<?php

include 'vendor/autoload.php';

use Async\Coroutine\FileSystem;

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

    $fd = yield FileSystem::open("./tmp", 'a', \UV::S_IRWXU | \UV::S_IRUSR);
    yield FileSystem::write($fd, "hello", 0);
    yield FileSystem::fdatasync($fd);
    yield FileSystem::close($fd);

    echo "\nThe task returned:\n";
    $result = yield \spawn_await(function () use ($command, $parameters) {
        return $command($parameters);
    });
    \var_dump($result);

    yield \shutdown();
};

\coroutine_run(\main());
