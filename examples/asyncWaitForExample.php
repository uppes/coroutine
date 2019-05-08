<?php
include 'vendor/autoload.php';

function eternity() {
    // Sleep for one hour
    print("\nAll good!\n");
    yield \async_sleep(3600);
    print(' yay!');
}

function keyboard() {
    // will begin outputs of `needName` in 1 second
    print("What's your name: ");
    yield \read_input('help', 5);
}

function needName() {
    $i = 1;
    yield \async_sleep(1);
    while(true) {
        echo $i;
        yield;
        $i++;
        if ($i == 10) {
            print(' hey! try again: ');
            break;
        }
    }
}

function main() {
    yield \await('needName');
    yield \keyboard();
    
    try {
        // Wait for at most 1 second
        yield \async_wait_for('eternity', 0.5);
	} catch (\RuntimeException $e) {
        print("\ntimeout!");
        // this script should have exited automatically, since 
        // there are no streams open, nor tasks running, this exception killed `eternity` task
        // currently, will continue to run
        yield \async_remove(2); // task id 2 is `ioSocketPoll` task, the scheduler added for listening for streams
        //exit();
	}
}

\coroutineCreate(\main());
\coroutineRun();
