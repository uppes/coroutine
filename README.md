[![Build status](https://ci.appveyor.com/api/projects/status/0sc1bycffhmu2ioo/branch/master?svg=true)](https://ci.appveyor.com/project/techno-express/coroutine/branch/master)[![Build Status](https://travis-ci.org/uppes/coroutine.svg?branch=master)](https://travis-ci.org/uppes/coroutine)[![codecov](https://codecov.io/gh/uppes/coroutine/branch/master/graph/badge.svg)](https://codecov.io/gh/uppes/coroutine)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/fbd1d327f0d14164833396e2fbdf492b)](https://app.codacy.com/app/techno-express/coroutine?utm_source=github.com&utm_medium=referral&utm_content=uppes/coroutine&utm_campaign=Badge_Grade_Dashboard)

### Table of Contents

* [Coroutine - Introduction & Background](#Coroutine)
* [Functions](#Functions)
* [Installation](#Installation)
* [Usage](#usage)
* [Development](#Development)
* [Todo](#Todo)
* [Package/Comparison](#Package/Comparison)
* [Credits/References](#Credits/References)
* [License](#license)

# Coroutine

A Coroutine are special functions that are based on __generators__, with the use of `yield` and `yield from`. When used they release the flow of control back to the calling function, with bring with an object.

This package represent that calling function, an __scheduler__, similar to an **event loop**. A coroutine needs to be scheduled to run, and once scheduled coroutines are wrapped in an `Task`, which are a type of **Promise**.

A `task` is an object that represents some work to be done, potentially with a result at the end of it. These tasks are _registered_ with a scheduler that is responsible for running them.

Due to the __single-threaded__ nature of PHP (without extensions anyway), we cannot think of a `task` as doing a single __long-running__ calculation - this will __block__ the single thread until the task is finished.

Instead, `tasks` must perform work in small chunks/iterations __('ticks')__ where possible, passing control back to the scheduler at appropriate points. This is known as [__cooperative multi-tasking__](https://en.wikipedia.org/wiki/Cooperative_multitasking) (so called because the tasks must cooperate by yielding control voluntarily).

The scheduler is responsible for 'ticking' the scheduled tasks, with each scheduled task being repeatedly 'ticked' until it is complete. It is up to the scheduler implementation how to do this in a way that allows all scheduled tasks to run.

A `task` can become complete in one of three ways:

    The task reaches successful completion, and optionally produces a result
    The task encounters an error and fails
    The task is cancelled by calling cancel()

When using this package, and the code you are working on contain `yield` points, these define points is where a *context switch* can happen if other tasks are pending, but will not if no other task is pending. This can also be seen as **breakpoints/traps**, like when using an debugger, when triggered, the debugger steps in, an you can view state and step thought the remainder of your code.

> A *context switch* represents the __scheduler__ yielding the flow of control from one *coroutine* to the next.

> A *coroutine* here is define as an function/method containing the `yield` keyword, in which will return *generator* object.

The **generator** object that's immediately returned, gives us access to few methods, that allow itself to progress.

So here we have a very special case with `Generators` in that it being part of the PHP language, and when looked at through the lens of how Promise's work, and that's to not block, just execute line and return. The main idea of being asynchronous.

Promises returns an object, that's placed into an event loop queue. The event loop does the actual executing the callback attached to the object. This is really a manual process, with much code state/overhead to manage. This is called an [Reactor pattern](https://en.wikipedia.org/wiki/Reactor_pattern) of execution, dispatches callbacks synchronously.

The **mechanics** of an event loop is already present when an a *generator* is put in motion. I see this as an [Proactor pattern](https://en.wikipedia.org/wiki/Proactor_pattern). Since the action of `yield`ing is the initiator, begins the process of checking resource availability, performing operations/actions at that moment, and handling/returning completion events, all asynchronously.

Take a read of this post, [What are coroutines in C++20?](https://stackoverflow.com/questions/43503656/what-are-coroutines-in-c20)

    There are two kinds of coroutines; stackful and stackless.

    A stackless coroutine only stores local variables in its state and its location of execution.

    A stackful coroutine stores an entire stack (like a thread).

    Stackless coroutines can be extremely light weight. The last proposal I read involved basically rewriting your function into something a bit like a lambda; all local variables go into the state of an object, and labels are used to jump to/from the location where the coroutine "produces" intermediate results.

    The process of producing a value is called "yield", as coroutines are bit like cooperative multithreading; you are yielding the point of execution back to the caller.

This package performs cooperative scheduling, the basics for multitasking, asynchronous programming.

The steps, that's taking place when an `yield` is introduced.

1. The *function* is now an `coroutine`.
2. The *object* returned is captured by the `scheduler`.
3. The *scheduler*, wraps this captured `generator` object around an `task` object.
4. The *task* object has additional methods and features, it could be seen as `promise` like.
5. The *task* is now place into an `task queue` controlled by the `scheduler`.
6. You **`run`** your `function`, putting everything in motion. *Here you are not starting any **event loop***. What could be seen as an event loop, is the work being done *before* or *after* the `task` is place into **action** by the `scheduler`.
7. Where will this `task` land/return to? *Answer*: The same location that called it, there are **no callbacks**.

> Step **1**, is implemented in other languages with an specific keyword, `async`.

> Steps **2** to **6**, is preformed in other languages with an specific keyword, `await`.

The terminology/naming used here is more in line with [Python's Asyncio](https://www.python.org/dev/peps/pep-0492/) and [Curio](https://curio.readthedocs.io/en/latest/index.html#) usage. In fact, most of the source code method calls has been change to match theres.

## Functions

Only the functions located here and in the `Core.php` file should be used. Direct access to object class libraries is discouraged, the names might change, or altogether drop if not listed here. Library package [development](#Development) is the exception.

```php
const MILLISECOND = 0.001;
const EOL = PHP_EOL;


/**
 * Makes an resolvable function from label name that's callable with `await`
 */
\async(string $labelFunction, $asyncFunction);

/**
 * Add/schedule an `yield`-ing `function/callable/task` for execution.
 * Returns an task Id
 * - This function needs to be prefixed with `yield`
 * 
 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
 */
yield \await($awaitedFunction, ...$args) ;

/**
 * Wrap the callable with `yield`, this makes sure every callable is a generator function,
 * and will switch at least once without actually executing.
 * - This function needs to be prefixed with `yield`
 *
 * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
 */
yield \awaitAble($awaitableFunction, ...$args);

/**
 * Run awaitable objects in the taskId sequence concurrently.
 * If any awaitable in taskId is a coroutine, it is automatically scheduled as a Task.
 *
 * If all awaitables are completed successfully, the result is an aggregate list of returned values.
 * The order of result values corresponds to the order of awaitables in taskId.
 *
 * The first raised exception is immediately propagated to the task that awaits on gather().
 * Other awaitables in the sequence won’t be cancelled and will continue to run.
 * - This function needs to be prefixed with `yield`
 *
 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.gather
 */
yield \gather(...$taskId);

/**
 * Block/sleep for delay seconds.
 * Suspends the calling task, allowing other tasks to run.
 * A result is returned If provided back to the caller
 * - This function needs to be prefixed with `yield`
 */
yield \async_sleep($delay, $result);

/**
 * Creates an communications Channel between coroutines, returns an object
 * - This function needs to be prefixed with `yield`
 */
yield \async_channel();

/**
 * Creates an Channel similar to Google Go language, returns an object
 * - This function needs to be prefixed with `yield`
 */
yield \go_make();

/**
 * Send message to an Channel
 * - This function needs to be prefixed with `yield`
 */
yield \go_sender($channel, $message, $taskId);

/**
 * Set task as Channel receiver
 * - This function needs to be prefixed with `yield`
 */
yield \go_receiver($channel);

/**
 * Receive Channel message, returns a message
 * - This function needs to be prefixed with `yield`
 */
yield \go_receive($channel);

/**
 * A goroutine is a function that is capable of running concurrently with other functions.
 * To create a goroutine we use the keyword `go` followed by a function invocation
 * @see https://www.golang-book.com/books/intro/10#section1
 */
yield \go($goFunction, ...$args);

/**
 * Block/sleep for delay seconds.
 * Suspends the calling task, allowing other tasks to run.
 * Returns $result back to caller
 */
yield \go_sleep($delay, $result);

/**
 * Wait for the callable to complete with a timeout.
 */
yield \async_wait_for($callable, $timeout);

yield \async_cancel($tid);

yield \async_id();

yield \async_read_wait($stream);

yield \async_write_wait($stream);
```

```php
/**
 * Add and wait for result of an blocking io subprocess, will run in parallel.
 * - This function needs to be prefixed with `yield`
 *
 * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
 * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
 */
yield \await_blocking($command, $timeout)
```

```php
yield \secure_server($uri, $options, $privatekeyFil, $certificateFile, $signingFile, $ssl_path, $details);

yield \create_server($uri, $options);

yield \create_client($uri, $options, $isRequest);

yield \client_read($socket, $size);

yield \client_write($socket, $response);

yield \close_client($socket);

yield \accept_socket($socket);

yield \read_socket($socket, $size);

yield \write_socket($socket, $response);

yield \close_Socket($socket);

yield \read_input($size = 1024);

// no yield
\remote_ip($socket);
```

```php
\coroutine_instance();

\coroutine_clear();

\coroutine_create($coroutine);

/**
 * This function runs the passed coroutine, taking care of managing the scheduler and
 * finalizing asynchronous generators. It should be used as a main entry point for programs, and
 * should ideally only be called once.
 *
 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.run
 */
\coroutine_run($coroutine);
```

```php
/**
 * Add something/callable to `coroutine` process pool
 */
\parallel($callable, $timeout);

/**
 * Get/create process worker pool of an parallel instance.
 */
\parallel_instance();

/**
 * Add something/callable to parallel instance process pool.
 */
\parallel_add($somethingToRun, $timeout);

/**
 * Execute process pool, wait for results. Will do other stuff come back later.
 */
\parallel_wait();
```

## Installation

    composer require uppes/coroutine

## Usage

Theses are in the examples folder.

```php
/**
 * @see https://docs.python.org/3/library/asyncio-task.html#timeouts
 */
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
    // Note: I have three Windows systems
    // - Windows 10 using PHP 7.2.16 (cli) (built: Mar  6 2019 21:52:05) ( NTS MSVC15 (Visual C++ 2017) x64 )
    // - Windows 10 using PHP 7.1.19 (cli) (built: Jun 20 2018 23:37:54) ( NTS MSVC14 (Visual C++ 2015) x86 )
    // NTS version blocks STDIN from the beginning on Windows
    // Windows can have non-blocking STDIN, ZTS has it, if no input attempted.
    // - Windows 7 using PHP 7.1.16 (cli) (built: Mar 28 2018 21:15:31) ( ZTS MSVC14 (Visual C++ 2015) x64 )
    // ZTS does not block, only after typing something it blocks
    // I need to file bug report, i see there are some old ones posted on the issue, there should be an fix for this.
    // Haven't tried any workarounds.
    yield \read_input();
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
        // Wait for at most 0.5 second
        yield \async_wait_for('eternity', 0.5);
    } catch (\RuntimeException $e) {
        print("\ntimeout!");
        // this script should have exited automatically, since
        // there are no streams open, nor tasks running, this exception killed `eternity` task
        // currently, will continue to run
        // task id 2 is `ioWaiting` task, the scheduler added for listening 
        // for stream socket connections
        yield \async_cancel(2);
        // This might just be because `main` is task 1, 
        // and still running by the exception throw, need more testing
    }
}

\coroutine_run(\main());
```

```php
/**
 * @see https://golangbot.com/goroutines/
 * @see https://play.golang.org/p/oltn5nw0w3
 */
include 'vendor/autoload.php';

function numbers() {
    for ($i = 1; $i <= 5; $i++) {
        yield \go_sleep(250 * \MILLISECOND);
        print(' '.$i);
    }
}

function alphabets() {
    for ($i = 'a'; $i <= 'e'; $i++) {
        yield \go_sleep(400 * \MILLISECOND);
        print(' '.$i);
    }
}

function main() {
    yield \go('numbers');
    yield \go('alphabets');
    yield \go_sleep(3000 * \MILLISECOND);
    print(" main terminated");
}

\coroutine_run(\main());
```

## Development

```php
/**
 * Template for developing an library package for access
 */
public static function someName($whatever, ...$args)
{
    return new Kernel(
        function(TaskInterface $task, Coroutine $coroutine) use ($whatever, $args){
            // Use/Execute/call some $whatever(...$args);


            // will return $someValue back to the caller
            $task->sendValue($someValue);
            // will return back to the caller, the callback
            $coroutine->schedule($task);
        }
    );
}

// Setup to call
function some_name($whatever, ...$args) {
    return Kernel::someName($whatever, ...$args);
}

// To use
yield \some_name($whatever, ...$args);
```

## Todo

* Add more standard examples from other languages, converted over.
* Update docs in reference to similar sections of functionally in Python, Go or any other languages.
* Add/implement Curio `spawn` method, and debugging/monitoring features.
* Turn some Http PSR-7, and PSR-17, package to something like Pythons aioHttp.
* Create an specific error/exception class for `coroutines`.
* Add/Update phpunit tests.

## Package/Comparison

The closest thing would be [Swoole PHP](https://www.swoole.co.uk/coroutine). However, it's not an standard installation, needs to be compiled, does not work on Windows, enforces there complete framework usage.

There is also [Facebook's Hack](https://hhvm.com/). However, this too not an standard installation, but instead nearly an whole different language.

____Other main asynchronous PHP libraries____

[Amp](https://github.com/amphp)

* Using *`yield`* generators. However, using *`Promises`* also, which mandates the normal *Event Loop*.
* Users would need to totally restructure the normal way they develope with there package. 
* There package necessitate there framework, all there packages bring in many files.
* Try creating the **Google's `Go`** like example with this package as I have an example of above, in the same number of lines.

[ReactPHP](https://github.com/reactphp) and [Guzzle Promises](https://github.com/guzzle/promises)

* Using *`Promises`*, which mandates the normal *Event Loop*. Neither can run each other promises without issues, if they following specs, the logic, they should be getting the same results, regardless of the internal code routines used. That necessitated my own [uppes/promiseplus](https://github.com/uppes/promiseplus), that runs both, which is archived.

[Recoil](https://github.com/recoilphp/recoil)

* Based on *ReactPHP*, but using `yield`, not using standard terminology/naming conventions, making it hard to follow. Many additional libraries, and files. But, it could run the the example above after much effort. In the end, not worth using, after all the additions, bringing in *ReactPHP* `Promise`'s and `Event Loop`.

The other libraries one might come across will either require an extension, or don't work on Windows. Regardless, all bring in many unnecessary files and not so intuitive to use. I mean that intuitive that sparks other usage, see the connections elsewhere.

---
This ___`Coroutine`___ package differs, mainly because it just managing the flow of control/execution. The calling function is the callback location, yielding lets other things run. This offers the developer freedom to build, go beyond what typical could be done in PHP. This package is presenting standard usage, as simple as possible.

## Credits/References

 **Nikita Popov** [Cooperative multitasking using coroutines (in PHP!)](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html). Which this package **forks** [Ditaio](https://github.com/nikic/ditaio), restructuring/rewriting.

 **Christopher Pitt** [Co-operative PHP Multitasking](https://medium.com/async-php/co-operative-php-multitasking-ce4ef52858a0)

**Parallel** class package here is a restructured/rewrite of [spatie/async](https://github.com/spatie/async). The old package following there implementation, but with _Windows_ support can be found [here](https://github.com/techno-express/async/tree/windows-patch).

**Parallel** class also pulls in [uppes/processor](https://github.com/uppes/processor) as an dependency which includes, [symfony/process](https://github.com/symfony/process) class, which is going to be used instead of my own implementation for **subprocess** management/execution. It has better **Windows** support, no issues running parallel PHP processes, not seeing any blocking issues.

---
[Concurrency in Go](https://youtu.be/LvgVSSpwND8) __video__

[Curious Course on Coroutines and Concurrency](https://youtu.be/Z_OAlIhXziw) __video__

[Cooperative multitasking with generators](https://youtu.be/cY8FUhZvK7w) __video__

[Common asynchronous patterns](https://youtu.be/jq2IFUQRbGo) __video__

[Get to grips with asyncio](https://youtu.be/M-UcUs7IMIM) __video__

[Raymond Hettinger, Keynote on Concurrency, PyBay 2017](https://youtu.be/9zinZmE3Ogk) __video__

[Understand Kotlin Coroutines on Android](https://youtu.be/BOHK_w09pVA) __video__

[Python 3.5+ Async: An Easier Way to do Concurrency](https://youtu.be/qfY2cqjJMdw) __video__

[The C# async await Workout](https://youtu.be/eV45ZgXU1Mk) __video__

[Generators: The Final Frontier - ScreenCast](https://youtu.be/5-qadlG7tWo) __video__

---

## Contributing

Contributions are encouraged and welcome; *_I especially need input/help with the ToDo's._* I am always happy to get feedback or pull requests on Github :) Create [Github Issues](https://github.com/uppes/coroutine/issues) for bugs and new features and comment on the ones you are interested in.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
