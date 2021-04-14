<?php
include 'vendor/autoload.php';

use Async\Exceptions\TimeoutError;

function eternity()
{
    // Sleep for one hour
    print("\nAll good!\n");
    yield \sleep_for(3600);
    print(' yay!');
}

function keyboard()
{
    // will begin outputs of `needName` in 1 second
    print("What's your name: ");
    return yield \input_wait();
}

function needName()
{
    $i = 1;
    yield \sleep_for(1);
    while (true) {
        echo $i;
        yield \sleep_for(0.05);
        $i++;
        if ($i == 15) {
            print(\EOL . 'hey! try again: ');
        }
        if ($i == 100) {
            print(\EOL . 'hey! try again, one more time: ');
            break;
        }
    }
}

function main()
{
    yield \away(\needName());
    echo \EOL . 'You typed: ' . (yield \keyboard()) . \EOL;

    try {
        // Wait for at most 0.5 second
        yield \wait_for(\eternity(), 0.5);
    } catch (TimeoutError $e) {
        print("\ntimeout!");
        // this script should have exited automatically, since
        // there are no streams open, nor tasks running, this exception killed `eternity` task
        // currently, will continue to run
        yield \cancel_task(2); // task id 2 is `ioSocketPoll` task, the scheduler added for listening for streams
        //exit();
    }
}

\coroutine_run(\main());
