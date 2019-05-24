<?php
include 'vendor/autoload.php';

function eternity() {
    // Sleep for one hour
    print("\nAll good!\n");
    yield \sleep_for(3600);
    print(' yay!');
}

function keyboard() {
    // will begin outputs of `needName` in 1 second
    print("What's your name: ");
    yield \read_input();
}

function needName() {
    $i = 1;
    yield \sleep_for(1);
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
        // Wait for at most 0.5 second
        yield \wait_for('eternity', 0.5);
	} catch (\RuntimeException $e) {
        print("\ntimeout!");
        // this script should have exited automatically, since 
        // there are no streams open, nor tasks running, this exception killed `eternity` task
        // currently, will continue to run
        yield \cancel_task(2); // task id 2 is `ioSocketPoll` task, the scheduler added for listening for streams
        //exit();
	}
}

\coroutine_run(\main());
