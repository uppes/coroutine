# Coroutine

[![Build status](https://ci.appveyor.com/api/projects/status/77foelnve2y1wgsb/branch/master?svg=true)](https://ci.appveyor.com/project/techno-express/coroutine-0fmbc/branch/master)![Coroutine](https://github.com/symplely/coroutine/workflows/Coroutine/badge.svg)[![codecov](https://codecov.io/gh/symplely/coroutine/branch/master/graph/badge.svg)](https://codecov.io/gh/symplely/coroutine)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/44a6f32f03194872b7d4cd6a2411ff79)](https://www.codacy.com/app/techno-express/coroutine?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=symplely/coroutine&amp;utm_campaign=Badge_Grade)[![Maintainability](https://api.codeclimate.com/v1/badges/1bfc3497fde67b111a04/maintainability)](https://codeclimate.com/github/symplely/coroutine/maintainability)

> This version `1.5.x` onward will begin adding features of an PHP extension [UV](https://github.com/bwoebi/php-uv), of **Node.js** [libuv](https://github.com/libuv/libuv) library, see the online [book](https://nikhilm.github.io/uvbook/index.html) for a full tutorial overview.

> Currently all `libuv` [network](https://github.com/bwoebi/php-uv/issues) `socket/stream/udp/tcp` like features are broken on *Windows*, as such will not be implemented, that includes *Linux*, which do work as expected.

## Table of Contents

* [Introduction](#introduction)
* [Functions](#functions)
* [Installation](#installation)
* [Usage](#usage)
* [Development](#development)
* [Todo](#todo)
* [Package/Comparison](#package/comparison)
* [Credits/References](#credits/references)
* [Contributing](#contributing)
* [License](#license)

## Introduction

__What is Async?__
Simply avoiding the blocking of very next instruction. The mechanism of *capturing* and *running later*.

__What issues the current PHP implementations this package address?__
*Ease of use*. [Amp](#[Amp]) and [ReactPHP](#[ReactPHP]) both do and get you asynchronous results. But both require more setup by the *__user/developer__* to get a simple line of code to run. There is also **Swoole** and **Hhvm**, but neither are standard PHP installations.

When using **Amp** or **ReactPHP** and some of the packages based upon them, you must not only manage the `Callbacks` you provided, but also the returned `Promise` object, and the `Event Loop` object. These libraries modeling themselves around old school **Javascript**, where `Javascript` nowadays moving towards simple `async/await` syntax. Which brings up this question.

__What does `ease of use` mean?__
We can start by reviewing other programming languages implementations. And the fact you already have a working application, that you want various parts to run more responsive, which translates to do more.

[Go](https://golang.org/doc/effective_go.html#goroutines) has it's **Goroutine** keyword `go` then your function and statements.

These links of [Python](https://docs.python.org/3/library/asyncio-task.html), [Ruby](https://ruby-concurrency.github.io/concurrent-ruby/1.1.4/Concurrent/Async.html), [Rust](https://rust-lang.github.io/async-book/01_getting_started/04_async_await_primer.html#asyncawait-primer), [Nim](https://nim-lang.org/docs/asyncnet.html), [C#](https://docs.microsoft.com/en-us/dotnet/csharp/programming-guide/concepts/async/), [Elixir](https://hexdocs.pm/elixir/Task.html#module-async-and-await), [Java](https://github.com/electronicarts/ea-async), and [C++](https://lewissbaker.github.io/2017/11/17/understanding-operator-co-await) details keywords `async/await` combined with a function and statements.

When using they return the actual results you want, no meddling with any objects, no callbacks, and upon calling everything to up that point continues to run.

The thing about each, is the history leading to the feature, the actual behavior, the underlying concepts are the same, and a simple calling structure statement. They all reference in various ways the use of `yield` or `iterators`, which this package relies upon and makes the following true.

The only thing you will do to **make** your code `asynchronous`, is **placing** `yield` _within_, and **prefix** _calling_ your **code** with `yield` to **get** _actual_ `results` you want.

With this package you will have a **PHP** version of `async/await`, by just using **`yield`**.

There are a few helper functions available to tie everything together. Mainly, `away()` that's similar to Python's [create_task()](https://docs.python.org/3/library/asyncio-task.html#id4), that behaves like Google's [go()](https://golang.org/doc/effective_go.html#goroutines) keyword, which is included here as a alias function `go()`.

By using, it immediately returns a number, that can be used with `gather()`, another Python like [function](https://docs.python.org/3/library/asyncio-task.html#asyncio.gather), which also behaves like Google's [WaitGroup](https://golang.org/pkg/sync/#WaitGroup). This will wait and return the result of a code distant/**detached** for running in the `background`.

-------

A **Coroutine** here are specially crafted functions that are based on __generators__, with the use of `yield` and `yield from`. When used, they **control context**, meaning `capture/release` an application's execution flow.

When `yield` is placed within an block of code, it indicates to the calling function, that an object will be returned instead, the code is not immediately executed.

This package represent that calling function, an __scheduler__, similar to an **event loop**. A coroutine needs to be scheduled to run, and once scheduled coroutines are wrapped in an `Task`, which are a type of **Promise**.

A `task` is an object that represents some work to be done, potentially with a result at the end of it. These tasks are _registered_ with a scheduler that is responsible for running them.

Due to the __single-threaded__ nature of PHP (without extensions anyway), we cannot think of a `task` as doing a single __long-running__ calculation - this will __block__ the single thread until the task is finished.

Instead, `tasks` must perform work in small chunks/iterations __('ticks')__ where possible, passing control back to the scheduler at appropriate points. This is known as [__cooperative multi-tasking__](https://en.wikipedia.org/wiki/Cooperative_multitasking) (so called because the tasks must cooperate by yielding control voluntarily).

The scheduler is responsible for 'ticking' the scheduled tasks, with each scheduled task being repeatedly 'ticked' until it is complete. It is up to the scheduler implementation how to do this in a way that allows all scheduled tasks to run.

A `task` can become complete in one of three ways:

```text
The task reaches successful completion, and optionally produces a result
The task encounters an error and fails
The task is cancelled by calling cancel()
```

When using this package, and the code you are working on contain `yield` points, these define points is where a *context switch* can happen if other tasks are pending, but will not if no other task is pending. This can also be seen as **breakpoints/traps**, like when using an debugger, when triggered, the debugger steps in, an you can view state and step thought the remainder of your code.

> A *context switch* represents the __scheduler__ yielding the flow of control from one *coroutine* to the next.

> A *coroutine* here is define as an function/method containing the `yield` keyword, in which will return *generator* object.

The **generator** object that's immediately returned, gives us access to few methods, that allow itself to progress.

So here we have a very special case with `Generators` in that it being part of the PHP language, and when looked at through the lens of how Promise's work, and that's to not block, just execute line and return. The main idea of being asynchronous.

Promises returns an object, that's placed into an event loop queue. The event loop does the actual executing the callback attached to the object. This is really a manual process, with much code state/overhead to manage. This is called an [Reactor pattern](https://en.wikipedia.org/wiki/Reactor_pattern) of execution, dispatches callbacks synchronously.

The **mechanics** of an event loop is already present when an a *generator* is put in motion. I see this as an [Proactor pattern](https://en.wikipedia.org/wiki/Proactor_pattern). Since the action of `yield`ing is the initiator, begins the process of checking resource availability, performing operations/actions at that moment, and handling/returning completion events, all asynchronously.

Take a read of this post, [What are coroutines in C++20?](https://stackoverflow.com/questions/43503656/what-are-coroutines-in-c20)

```text
There are two kinds of coroutines; stackful and stackless.

A stackless coroutine only stores local variables in its state and its location of execution.

A stackful coroutine stores an entire stack (like a thread).

Stackless coroutines can be extremely light weight. The last proposal I read involved basically rewriting your function into something a bit like a lambda; all local variables go into the state of an object, and labels are used to jump to/from the location where the coroutine "produces" intermediate results.

The process of producing a value is called "yield", as coroutines are bit like cooperative multithreading; you are yielding the point of execution back to the caller.
```

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

This package should be seen/used as an **user-land** extension, it's usage of `yield` has been envisioned from [RFC](https://wiki.php.net/rfc/generator-delegation) creators.

The [Lua](https://www.lua.org/pil/9.4.html) language has similar functionality.

## Functions

Only the functions located here and in the `Core.php` file should be used. Direct access to object class libraries is discouraged, the names might change, or altogether drop if not listed here. Library package [development](#Development) is the exception.

```php
const MILLISECOND = 0.001;
const EOL = PHP_EOL;
const DS = DIRECTORY_SEPARATOR;

/**
 * Makes an resolvable function from label name that's callable with `away`
 */
\async(string $labelFunction, $asyncFunction);

/**
 * Wrap the value with `yield`, when placed within this insure that
 * any *function/method* will be `awaitable` and the actual return
 * value is picked up properly by `gather()`.
 */
return \value($value)

/**
 * Add/schedule an `yield`-ing `function/callable/task` for background execution.
 * Will immediately return an `int`, and continue to the next instruction.
 * Returns an task Id
 * - This function needs to be prefixed with `yield`
 *
 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
 */
yield \away($awaitedFunction, ...$args) ;

/**
 * Performs a clean application exit and shutdown.
 *
 * Provide $skipTask incase called by an Signal Handler. Defaults to the main parent task.
 * - Use `get_task()` to retrieve caller's task id.
 *
 * - This function needs to be prefixed with `yield`
 */
yield \shutdown($skipTask)

/**
 * Wrap the callable with `yield`, this insure the first attempt to execute will behave
 * like a generator function, will switch at least once without actually executing, return object instead.
 * This function is used by `away` not really called directly.
 *
 * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
 */
\awaitAble($awaitableFunction, ...$args);

/**
 * Run awaitable objects in the tasks set concurrently and block until the condition specified by race.
 *
 * Controls how the `gather()` function operates.
 * `gather_wait` will behave like **Promise** functions `All`, `Some`, `Any` in JavaScript.
 *
 * - This function needs to be prefixed with `yield`
 *
 * @see https://docs.python.org/3.7/library/asyncio-task.html#waiting-primitives
 */
yield \gather_wait(array $tasks, int $race = 0, bool $exception = true, bool $clear = true)

/**
 * Run awaitable objects in the taskId sequence concurrently.
 * If any awaitable in taskId is a coroutine, it is automatically scheduled as a Task.
 *
 * If all awaitables are completed successfully, the result is an aggregate list of returned values.
 * The order of result values corresponds to the order of awaitables in taskId.
 *
 * The first raised exception is immediately propagated to the task that awaits on gather().
 * Other awaitables in the sequence won't be cancelled and will continue to run.
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
yield \sleep_for($delay, $result);

/**
 * Creates an communications Channel between coroutines, returns an object
 * Similar to Google Go language - basic, still needs additional functions
 * - This function needs to be prefixed with `yield`
 */
yield \make();

/**
 * Send message to an Channel
 * - This function needs to be prefixed with `yield`
 */
yield \sender($channel, $message, $taskId);

/**
 * Set task as Channel receiver, and wait to receive Channel message
 * Will continue other tasks until so.
 * - This function needs to be prefixed with `yield`
 */
yield \receiver($channel);

/**
 * A goroutine is a function that is capable of running concurrently with other functions.
 * To create a goroutine we use the keyword `go` followed by a function invocation
 * @see https://www.golang-book.com/books/intro/10#section1
 */
yield \go($goFunction, ...$args);

/**
 * Modeled as in `Go` Language.
 *
 * The behavior of defer statements is straightforward and predictable.
 * There are three simple rules:
 * 1. *A deferred function's arguments are evaluated when the defer statement is evaluated.*
 * 2. *Deferred function calls are executed in Last In First Out order after the* surrounding function returns.
 * 3. *Deferred functions can`t modify return values when is type, but can modify content of reference to
 *
 * @see https://golang.org/doc/effective_go.html#defer
 */
\defer(&$previous, $callback)

/**
 * Modeled as in `Go` Language.
 *
 * Regains control of a panicking `task`.
 *
 * Recover is only useful inside `defer()` functions. During normal execution, a call to recover will return nil
 * and have no other effect. If the current `task` is panicking, a call to recover will capture the value given
 * to panic and resume normal execution.
 */
\recover(&$previous, $callback);

/**
 * Modeled as in `Go` Language.
 *
 * An general purpose function for throwing an Coroutine `Exception`,
 * or some abnormal condition needing to keep an `task` stack trace.
 */
\panic($message, $code, $previous);

/**
 * Return the task ID
 * - This function needs to be prefixed with `yield`
 */
yield \get_task();

/**
 * kill/remove an task using task id
 * - This function needs to be prefixed with `yield`
 */
yield \cancel_task($tid);

/**
 * Add/execute a blocking `subprocess` task that runs in parallel.
 * This function will return `int` immediately, use `gather()` to get the result.
 * - This function needs to be prefixed with `yield`
 */
yield \spawn_task($command, $timeout);

/**
 * Wait for the callable/task to complete with a timeout.
 * Will continue other tasks until so.
 * - This function needs to be prefixed with `yield`
 */
yield \wait_for($callable, $timeout);

/**
 * Wait on read stream/socket to be ready read from.
 * Will continue other tasks until so.
 * - This function needs to be prefixed with `yield`
 */
yield \read_wait($stream);

/**
 * Wait on write stream/socket to be ready to be written to.
 * Will continue other tasks until so.
 * - This function needs to be prefixed with `yield`
 */
yield \write_wait($stream);

/**
 * Wait on keyboard input.
 * Will continue other tasks until so.
 * Will not block other task on `Linux`, will continue other tasks until `enter` key is pressed,
 * Will block on Windows, once an key is typed/pressed, will continue other tasks `ONLY` if no key is pressed.
 * - This function needs to be prefixed with `yield`
 */
yield \input_wait($size);

/**
 * An PHP Functional Programming Primitive.
 * Return a curryied version of the given function. You can decide if you also
 * want to curry optional parameters or not.
 *
 * @see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#currying
 */
\curry($function, $required);
```

```php
/**
 * Add and wait for result of a blocking `subprocess` that runs in parallel.
 * - This function needs to be prefixed with `yield`
 *
 * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
 * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
 */
yield \await_process($command, $timeout);
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
\parallel_pool();

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

```cmd
composer require symplely/coroutine
```

This version will use **libuv** features if available. Do one of the following to install.

For **Debian** like distributions, Ubuntu...

```bash
apt-get install libuv1-dev php-pear -y
```

For **RedHat** like distributions, CentOS...

```bash
yum install libuv-devel php-pear -y
```

Now have **Pecl** auto compile, install, and setup.

```bash
pecl channel-update pecl.php.net
pecl install uv-beta
```

For **Windows** there is good news, native *async* thru `libuv` has arrived.

Windows builds for stable PHP versions are available [from PECL](https://pecl.php.net/package/uv).

Directly download latest from https://windows.php.net/downloads/pecl/releases/uv/0.2.4/

Extract `libuv.dll` to sample directory as `PHP` binary executable, and extract `php_uv.dll` to `ext\` directory.

Enable extension `php_sockets.dll` and `php_uv.dll` in php.ini

```powershell
cd C:\Php
Invoke-WebRequest "https://windows.php.net/downloads/pecl/releases/uv/0.2.4/php_uv-0.2.4-7.2-ts-vc15-x64.zip" -OutFile "php_uv-0.2.4.zip"
#Invoke-WebRequest "https://windows.php.net/downloads/pecl/releases/uv/0.2.4/php_uv-0.2.4-7.3-nts-vc15-x64.zip" -OutFile "php_uv-0.2.4.zip"
#Invoke-WebRequest "https://windows.php.net/downloads/pecl/releases/uv/0.2.4/php_uv-0.2.4-7.4-ts-vc15-x64.zip" -OutFile "php_uv-0.2.4.zip"
7z x -y php_uv-0.2.4.zip libuv.dll php_uv.dll
copy php_uv.dll ext\php_uv.dll
del php_uv.dll
del php_uv-0.2.4.zip
echo extension=php_sockets.dll >> php.ini
echo extension=php_uv.dll >> php.ini
```

## Usage

In general, any method/function having the `yield` keyword, will operate as an interruption point, suspend current routine, do something else, then return/resume. For an quick how to watch [Advanced Asyncio: Solving Real World Production Problems](https://youtu.be/yKfenooKl6M).

It's advisable to watch this [introduction, 4 hour video](https://youtu.be/D1twn9kLmYg), the concepts, the internals of the talk is what's taking place here. Another good article, [Kotlin: Diving in to Coroutines and Channels](https://proandroiddev.com/kotlin-coroutines-channels-csp-android-db441400965f), **Android** and **Java** base, it's take on coroutines, is what this package attempts to implement.

```php
function main() {
    // Your initialization/startup code will need to be enclosed inside an function.
    // This is required for proper operations to start.
}

\coroutine_run(\main());
```

There after, review as below, the scripts in the [examples](https://github.com/symplely/coroutine/tree/master/examples) folder.

```php
/**
 * @see https://docs.python.org/3/library/asyncio-task.html#timeouts
 */
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
    // Note: I have three Windows systems
    // - Windows 10 using PHP 7.2.18 (cli) (built: Apr 30 2019 23:32:39) ( ZTS MSVC15 (Visual C++ 2017) x64 )
    // - Windows 10 using PHP 7.1.19 (cli) (built: Jun 20 2018 23:37:54) ( NTS MSVC14 (Visual C++ 2015) x86 )
    // - Windows 7 using PHP 7.1.16 (cli) (built: Mar 28 2018 21:15:31) ( ZTS MSVC14 (Visual C++ 2015) x64 )
    // Windows 10 blocks STDIN from the beginning with no key press.
    // Windows 7 does non-blocking STDIN, if no input attempted. only after typing something it blocks.
    return yield \input_wait();
}

function needName() {
    $i = 1;
    yield \sleep_for(1);
    while(true) {
        echo $i;
        yield \sleep_for(0.05);
        $i++;
        if ($i == 15) {
            print(\EOL.'hey! try again: ');
        }
        if ($i == 100) {
            print(\EOL.'hey! try again, one more time: ');
            break;
        }
    }
}

function main() {
    yield \away(\needName());
    echo \EOL.'You typed: '.(yield \keyboard()).\EOL;

    try {
        // Wait for at most 0.5 second
        yield \wait_for(\eternity(), 0.5);
    } catch (\RuntimeException $e) {
        print("\ntimeout!");
        // this script should have exited automatically, since
        // there are no streams open, nor tasks running, this exception killed `eternity` task
        // currently, will continue to run
        // task id 2 is `ioWaiting` task, the scheduler added for listening
        // for stream socket connections
        yield \cancel_task(2);
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
        yield \sleep_for(250 * \MILLISECOND);
        print(' '.$i);
    }
}

function alphabets() {
    for ($i = 'a'; $i <= 'e'; $i++) {
        yield \sleep_for(400 * \MILLISECOND);
        print(' '.$i);
    }
}

function main() {
    yield \go(\numbers());
    yield \go(\alphabets());
    yield \sleep_for(3000 * \MILLISECOND);
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
* Add/Update phpunit tests.

## Package/Comparison

The closest thing would be [Swoole PHP](https://www.swoole.co.uk/coroutine). However, it's not an standard installation, needs to be compiled, does not work on Windows, enforces there complete framework usage.

You can also use [Facebook's Hack](https://hhvm.com/). However, this too not an standard installation, but instead nearly an whole different language.

The [**PHP Internals**](https://phpinternals.news/11) team has recently released [Parallel](https://www.php.net/manual/en/book.parallel.php). It's available on [PECL](https://pecl.php.net/package/parallel), [krakjoe/parallel](https://github.com/krakjoe/parallel). Looks promising, however this ___`Coroutine`___ package does everything listed in there **Parallel concurrency API** without the unnecessary restrictions, PHP 7.2, and limits on **Tasks**. They too modeling after Google's Go ease of use, but still directly handling the **Future/Promise** concepts. Whenever, some examples are produced, will recreate and benchmark the examples here. Will also create alias function calls for items with the same functionality.

There is also [Async Extension for PHP](https://github.com/concurrent-php/ext-async#async-extension-for-php). Haven't seen this before starting this project, similar concepts but require a lot more coding to do simply things, require *PHP 7.3*, and no *PECL* module. Has *Async* and *Await* keywords, but definitely not following the norms in usage as other languages. No way near the way Google's Go, Pythons, or C# work, seeing there [examples](https://github.com/concurrent-php/ext-async/tree/master/examples).

> The **Async Extension for PHP** seemed to have stopped development, and the main repo removed for unknown reasons. It has been forked at [dreamsxin/ext-async](https://github.com/dreamsxin/ext-async), with the [examples](https://github.com/dreamsxin/ext-async/tree/master/examples). The downloadable pre-compiled DLL **Windows** module was link to the author repo, which too no longer exists.

____Other main asynchronous PHP libraries____

### [Amp](https://github.com/amphp)

* Using *`yield`* generators. However, using *`Promises`* also, which mandates the normal *Event Loop*.
* Users would need to totally restructure the normal way they develope with there package.
* There package necessitate there framework, all there packages bring in many files.
* Try creating the **Google's `Go`** like example with this package as I have an example of above, in the same number of lines.

### [ReactPHP](https://github.com/reactphp)

### [Guzzle Promises](https://github.com/guzzle/promises)

* Using *`Promises`*, which mandates the normal *Event Loop*. Neither can run each other promises without issues, if they following specs, the logic, they should be getting the same results, regardless of the internal code routines used. That necessitated my own [techno-express/promiseplus](https://github.com/techno-express/promiseplus), that runs both, which is archived.

### [Recoil](https://github.com/recoilphp/recoil)

* Based on *ReactPHP*, but using `yield`, not using standard terminology/naming conventions, making it hard to follow. Many additional libraries, and files. But, it could run the the example above after much effort. In the end, not worth using, after all the additions, bringing in *ReactPHP* `Promise`'s and `Event Loop`.

The other libraries one might come across will either require an extension, or don't work on Windows. Regardless, all bring in many unnecessary files and not so intuitive to use. I mean that intuitive that sparks other usage, see the connections elsewhere.

---
This ___`Coroutine`___ package differs, mainly because it just managing the flow of control/execution. The calling function is the callback location, yielding lets other things run. This offers the developer freedom to build, go beyond what typical could be done in PHP. This package is presenting standard usage, as simple as possible.

## Credits/References

 **Nikita Popov** [Cooperative multitasking using coroutines (in PHP!)](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html). Which this package **forks** [Ditaio](https://github.com/nikic/ditaio), restructuring/rewriting.

 **Christopher Pitt** [Co-operative PHP Multitasking](https://medium.com/async-php/co-operative-php-multitasking-ce4ef52858a0)

**Parallel** class is a restructured/rewrite of [spatie/async](https://github.com/spatie/async). The **Parallel** class rely upon [symplely/spawn](https://github.com/symplely/spawn) as a dependency, used for **subprocess** management/execution, it uses **`uv_spawn`** of **libuv** for launching processes. The **Spawn** package has [opis/closure](https://github.com/opis/closure) as an dependency, used to overcome **PHP** serialization limitations, and [symfony/process](https://github.com/symfony/process) as a fallback to **`proc_open`** for launching processes, in case **libuv** the _PHP_-**UV** extension is not installed.

---
[Build Your Own Async](https://youtu.be/Y4Gt3Xjd7G8) __video__

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

Contributions are encouraged and welcome; I am always happy to get feedback or pull requests on Github :) Create [Github Issues](https://github.com/symplely/coroutine/issues) for bugs and new features and comment on the ones you are interested in.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
