<?php
include 'vendor/autoload.php';

// will create closure function in global namespace with supplied name as variable
\async('childTask', function ($av = null)
{
    $tid = yield \task_id();
    while (true) {
        echo "Child task $tid still alive! $av\n";
        yield;
    }
});

function parentTask()
{
    // place the variable global name in local namespace
    global $childTask;

    $tid = yield \task_id();
    // have `await` access the created async closure functions
    $childTid = yield \await($childTask('using async() function'));

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield \cancel_task($childTid);
    }
};

\coroutine_run(\parentTask());
