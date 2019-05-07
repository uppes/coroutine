<?php
include 'vendor/autoload.php';

function eternity() {
    // Sleep for one hour
    print("all good!\n");
    yield \async_sleep(3600);
    print('yay!');
}

function main() {
    // Wait for at most 1 second
    try {
        yield \async_Wait_for('eternity', 1.0);
	} catch (\RuntimeException $e) {
        print('timeout!');
        // this script should have exited automatically, since 
        // there are no streams open, nor tasks running, this exception killed `eternity` task
        // currently, will continue to run
        yield \async_remove(2); // task id 2 is `ioSocketPoll` task, the scheduler added for listening for streams
        //exit();
	}
}

\coroutineCreate(\main());
\coroutineRun();
