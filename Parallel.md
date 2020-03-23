Parallel
=====

An Asynchronous Parallel PHP process manager API.

```php
include 'vendor/autoload.php';

use Async\Coroutine\Parallel;

$parallel = new Parallel();

foreach ($things as $thing) {
        // the second argument is optional, it sets The maximum amount of time a process may take to finish in seconds.
    $parallel->add(function () use ($thing) {
        // Do a thing
    }, $optional)->then(function ($output) {
        // Handle success
    })->catch(function (\Throwable $exception) {
        // Handle exception
    });
}

$parallel->wait();
```

Event hooks
-------

When creating asynchronous processes, you'll get an instance of `ParallelInterface` returned.
You can add the following event hooks on a process.

```php
$parallel
    ->add(function () {
        // the second argument is optional, it sets The maximum amount of time a process may take to finish in seconds. Defaults 300.
    }, int $timeout = 300)
    ->then(function ($output) {
        // On success, `$output` is returned by the process or callable you passed to the queue.
    })
    ->catch(function ($exception) {
        // When an exception is thrown from within a process, it's caught and passed here.
    })
;
```

Error handling
-------

If an `Exception` or `Error` is thrown from within a child process, it can be caught per process by specifying a callback in the `->catch()` method.

```php
$parallel
    ->add(function () {
        // ...
    })
    ->catch(function ($exception) {
        // Handle the thrown exception for this child process.
    })
;
```

If there's no error handler added, the error will be thrown in the parent process when calling `parallel_wait()` or `$parallel->wait()`.

If the child process would unexpectedly stop without throwing an `Throwable`, the output written to `stderr` will be wrapped and thrown in the parent process.

Parallel pool configuration
----

You're free to create as many parallel process pools as you want, each parallel pool has its own queue of processes it will handle.

A parallel pool is configurable by the developer:

```php
use Async\Coroutine\Parallel;

$parallel = (new Parallel())

// The maximum amount of processes which can run simultaneously.
    ->concurrency(20)

// Configure how long the loop should sleep before re-checking the process statuses in milliseconds.
    ->sleepTime(50000);
```

___Behind the curtains___

When using this package, you're probably wondering what's happening underneath the surface.

We're using `uv_spawn`, and `proc_open` as a fallback, to create and manage child processes in PHP. By creating child processes on the fly, we're able to execute PHP scripts in parallel. This parallelism can improve performance significantly when dealing with multiple __Synchronous I/O__ tasks, which don't really need to wait for each other.

By giving these tasks a separate process to run on, the underlying operating system can take care of running them in parallel.

There's a caveat when dynamically spawning processes: you need to make sure that there won't be too many processes at once, or the application might crash.
The `Parallel` class provided by this package takes care of handling as many processes as you want by scheduling and running them when it's possible.

When multiple processes are spawned, each can have a separate time to completion.
One process might eg. have to wait for a HTTP call, while the other has to process large amounts of data.
Sometimes you also have points in your code which have to wait until the result of a process is returned.

This is why we have to wait at a certain point in time: for all processes on a parallel pool to finish,
so we can be sure it's safe to continue without accidentally killing the child processes which aren't done yet.

Waiting for all processes is done by using `yield`, `uv_run`, and `stream_select`, which will monitor until all processes are finished.

When a process is finished, its success event is triggered, which you can hook into with the `->then()` function.
Likewise, when a process fails or times out, the iterations will update that process' status and move on.
