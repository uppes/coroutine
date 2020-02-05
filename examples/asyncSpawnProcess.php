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

function enqueue($index, $task)
{
    echo 'started ' . $index . \EOL;
    // return to caller, let other tasks start, otherwise block after
    $result = \file_get_contents($task);

    $tid = yield \get_task();
    \printf("\nRead from %d, task %d: %d bytes\n", $index, $tid, \strlen($result));
    return $result;
};

// Event loop for parallel tasks
function main()
{
    global $results, $tasks;

    $coroutinesId = [];
    foreach ($tasks as $index => $parameters) {
        echo 'started ' . $index . ' ' . $parameters . \EOL;
        $coroutinesId[] = yield \spawn_process(function () use ($parameters) {
            return \file_get_contents($parameters);
        });
    }

    try {
        // will throw exception and stop/kill progress printout '.' after 1 seconds
        yield \wait_for(\repeat(), 1);
    } catch (\Async\Coroutine\Exceptions\TimeoutError $e) {
        $results = yield \gather($coroutinesId);
        foreach($results as $tid => $result) {
            \printf("\nRead from task %d: %d bytes\n", $tid, \strlen($result[0]));
        }
    }
};

\coroutine_run(\main());

echo "\nResult array keys:\n";
echo \var_export(\array_keys($results), true);
