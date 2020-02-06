<?php

/**
 * @see https://github.com/amphp/parallel/blob/master/examples/worker-pool.php
 */
include 'vendor/autoload.php';

// A variable to store our fetched results
$results = [];
// We can first define tasks and then run them
$tasks = ['http://php.net', 'https://github.com'];

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

// Event loop for parallel tasks
function main()
{
    global $results, $tasks;

    $coroutinesId = [];
    foreach ($tasks as $index => $parameters) {
        echo 'started ' . $index . ' ' . $parameters . \EOL;
        $coroutinesId[] = yield \spawn_task(function () use ($parameters) {
            return \file_get_contents($parameters);
        });
    }

    try {
        // will throw exception and stop/kill progress printout '.' after 1 seconds
        yield \wait_for(\repeat(), 1);
    } catch (\Async\Coroutine\Exceptions\TimeoutError $e) {
        $results = yield \gather($coroutinesId);
        foreach($results as $tid => $result) {
            \printf("\nRead from task %d: %d bytes\n", $tid, \strlen($result));
        }
    }
};

\coroutine_run(\main());
