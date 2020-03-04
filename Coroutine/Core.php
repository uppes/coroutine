<?php

declare(strict_types=1);

use Async\Coroutine\Defer;
use Async\Coroutine\Kernel;
use Async\Coroutine\Channel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoroutineInterface;
use Async\Processor\Processor;
use Async\Coroutine\ParallelInterface;
use Async\Processor\LauncherInterface;
use Async\Coroutine\Exceptions\Panic;
use Async\Coroutine\FileSystem;
use Async\Processor\Channel as Channeled;
use Async\Processor\ChannelInterface;

if (!\function_exists('coroutine_run')) {
    \define('MILLISECOND', 0.001);
    \define('EOL', \PHP_EOL);
    \define('DS', \DIRECTORY_SEPARATOR);
    if (!defined('_DS'))
        \define('_DS', \DIRECTORY_SEPARATOR);
    \define('IS_WINDOWS', ('\\' === \DS));
    \define('IS_LINUX', ('/' === \DS));

    if (\IS_WINDOWS && \function_exists('uv_loop_new')) {
        /**
         * The SIGUSR1 signal is sent to a process to indicate user-defined conditions.
         */
        \define('SIGUSR1', 10);

        /**
         * The SIGUSR2 signa2 is sent to a process to indicate user-defined conditions.
         */
        \define('SIGUSR2', 12);

        /**
         * The SIGHUP signal is sent to a process when its controlling terminal is closed.
         */
        \define('SIGHUP', 1);

        /**
         * The SIGINT signal is sent to a process by its controlling terminal
         * when a user wishes to interrupt the process.
         */
        \define('SIGINT', 2);

        /**
         * The SIGQUIT signal is sent to a process by its controlling terminal
         * when the user requests that the process quit.
         */
        \define('SIGQUIT', 3);

        /**
         * The SIGILL signal is sent to a process when it attempts to execute an illegal,
         * malformed, unknown, or privileged instruction.
         */
        \define('SIGILL', 4);

        /**
         * The SIGTRAP signal is sent to a process when an exception (or trap) occurs.
         */
        \define('SIGTRAP', 5);

        /**
         * The SIGABRT signal is sent to a process to tell it to abort, i.e. to terminate.
         */
        \define('SIGABRT', 6);

        \define('SIGIOT', 6);

        /**
         * The SIGBUS signal is sent to a process when it causes a bus error.
         */
        \define('SIGBUS', 7);

        \define('SIGFPE', 8);

        /**
         * The SIGKILL signal is sent to a process to cause it to terminate immediately (kill).
         */
        \define('SIGKILL', 9);

        /**
         * The SIGSEGV signal is sent to a process when it makes an invalid virtual memory reference, or segmentation fault,
         */
        \define('SIGSEGV', 11);

        /**
         * The SIGPIPE signal is sent to a process when it attempts to write to a pipe without
         * a process connected to the other end.
         */
        \define('SIGPIPE', 13);

        /**
         * The SIGALRM, SIGVTALRM and SIGPROF signal is sent to a process when the time limit specified
         * in a call to a preceding alarm setting function (such as setitimer) elapses.
         */
        \define('SIGALRM', 14);

        /**
         * The SIGTERM signal is sent to a process to request its termination.
         * Unlike the SIGKILL signal, it can be caught and interpreted or ignored by the process.
         */
        \define('SIGTERM', 15);

        \define('SIGSTKFLT', 16);
        \define('SIGCLD', 17);

        /**
         * The SIGCHLD signal is sent to a process when a child process terminates, is interrupted,
         * or resumes after being interrupted.
         */
        \define('SIGCHLD', 17);

        /**
         * The SIGCONT signal instructs the operating system to continue (restart) a process previously paused by the
         * SIGSTOP or SIGTSTP signal.
         */
        \define('SIGCONT', 18);

        /**
         * The SIGSTOP signal instructs the operating system to stop a process for later resumption.
         */
        \define('SIGSTOP', 19);

        /**
         * The SIGTSTP signal is sent to a process by its controlling terminal to request it to stop (terminal stop).
         */
        \define('SIGTSTP', 20);

        /**
         * The SIGTTIN signal is sent to a process when it attempts to read in from the tty while in the background.
         */
        \define('SIGTTIN', 21);

        /**
         * The SIGTTOU signal is sent to a process when it attempts to write out from the tty while in the background.
         */
        \define('SIGTTOU', 22);

        /**
         * The SIGURG signal is sent to a process when a socket has urgent or out-of-band data available to read.
         */
        \define('SIGURG', 23);

        /**
         * The SIGXCPU signal is sent to a process when it has used up the CPU for a duration that exceeds a certain
         * predetermined user-settable value.
         */
        \define('SIGXCPU', 24);

        /**
         * The SIGXFSZ signal is sent to a process when it grows a file larger than the maximum allowed size
         */
        \define('SIGXFSZ', 25);

        /**
         * The SIGVTALRM signal is sent to a process when the time limit specified in a call to a preceding alarm setting
         * function (such as setitimer) elapses.
         */
        \define('SIGVTALRM', 26);

        /**
         * The SIGPROF signal is sent to a process when the time limit specified in a call to a preceding alarm setting
         * function (such as setitimer) elapses.
         */
        \define('SIGPROF', 27);

        /**
         * The SIGWINCH signal is sent to a process when its controlling terminal changes its size (a window change).
         */
        \define('SIGWINCH', 28);

        /**
         * The SIGPOLL signal is sent when an event occurred on an explicitly watched file descriptor.
         */
        \define('SIGPOLL', 29);

        \define('SIGIO', 29);

        /**
         * The SIGPWR signal is sent to a process when the system experiences a power failure.
         */
        \define('SIGPWR', 30);

        /**
         * The SIGSYS signal is sent to a process when it passes a bad argument to a system call.
         */
        \define('SIGSYS', 31);

        \define('SIGBABY', 31);
    }

    /**
     * Makes an resolvable function from label name that's callable with `away`
     * The passed in `function/callable/task` is wrapped to be `awaitAble`
     *
     * This will create closure function in global namespace with supplied name as variable.
     *
     * @param string $labelFunction
     * @param Generator|callable $asyncFunction
     */
    function async(string $labelFunction, callable $asyncFunction)
    {
        Kernel::async($labelFunction, $asyncFunction);
    }

    /**
     * Wrap the value with `yield`, when placed within this insure that
     * any *function/method* will be `awaitable` and the actual return
     * value is picked up properly by `gather()`.
     *
     * use as: `return \value($value);`
     *
     * @param mixed $value
     *
     * @return Generator<mixed,mixed>
     */
    function value($value)
    {
        yield;
        return yield $value;
    }

    /**
     * Add/schedule an `yield`-ing `function/callable/task` for background execution.
     * Will immediately return an `int`, and continue to the next instruction.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
     *
     * - This function needs to be prefixed with `yield`
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args - if `generator`, $args can hold `customState`, and `customData`
     *
     * @return int $task id
     */
    function away($awaitableFunction, ...$args)
    {
        return Kernel::away($awaitableFunction, ...$args);
    }

    /**
     * Run awaitable objects in the tasks set concurrently and block until the condition specified by race.
     *
     * Controls how the `gather()` function operates.
     * `gather_wait` will behave like **Promise** functions `All`, `Some`, `Any` in JavaScript.
     *
     * @param array<int|\Generator> $tasks
     * @param int $race - If set, initiate a competitive race between multiple tasks.
     * - When amount of tasks as completed, the `gather` will return with task results.
     * - When `0` (default), will wait for all to complete.
     * @param bool $exception - If `true` (default), the first raised exception is immediately
     *  propagated to the task that awaits on gather().
     * Other awaitables in the aws sequence won't be cancelled and will continue to run.
     * - If `false`, exceptions are treated the same as successful results, and aggregated in the result list.
     * @param bool $clear - If `true` (default), close/cancel remaining results
     * @throws \LengthException - If the number of tasks less than the desired $race count.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#waiting-primitives
     *
     * @return array associative `$taskId` => `$result`
     */
    function gather_wait(array $tasks, int $race = 0, bool $exception = true, bool $clear = true)
    {
        return Kernel::gatherWait($tasks, $race, $exception, $clear);
    }

    /**
     * Run awaitable objects in the taskId sequence concurrently.
     * If any awaitable in taskId is a coroutine, it is automatically scheduled as a Task.
     *
     * If all awaitables are completed successfully, the result is an aggregate list of returned values.
     * The order of result values corresponds to the order of awaitables in taskId.
     *
     * The first raised exception is immediately propagated to the task that awaits on gather().
     * Other awaitables in the sequence won't be cancelled and will continue to run.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.gather
     *
     * - This function needs to be prefixed with `yield`
     *
     * @param int|array $taskId
     * @return array associative `$taskId` => `$result`
     */
    function gather(...$taskId)
    {
        return Kernel::gather(...$taskId);
    }

    /**
     * Add/execute a blocking `I/O` subprocess task that runs in parallel.
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     *
     * @return int
     */
    function spawn_task($command, $timeout = 60, bool $display = false, $channel = null, $channelTask = null)
    {
        return Kernel::spawnTask($command, $timeout, $display, $channel, $channelTask);
    }

    /**
     * Add and wait for result of an blocking `I/O` subprocess that runs in parallel.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     *
     * @return mixed
     */
    function spawn_await($callable, $timeout = 60, bool $display = false, $channel = null, $channelTask = null)
    {
        return \awaitable_process(function () use ($callable, $timeout, $display, $channel, $channelTask) {
            return Kernel::addProcess($callable, $timeout, $display, $channel, $channelTask);
        });
    }

    /**
     * Executes a blocking system call asynchronously in a **child/subprocess**.
     * Use if `libuv` is not installed.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $command Any `PHP` builtin system operation command.
     * @param mixed ...$parameters
     *
     * @return  mixed
     * @throws Exception if not a callable.
     */
    function spawn_system(string $command, ...$parameters)
    {
        if (!\is_callable($command)) {
            \panic('Not a valid PHP callable command!');
        }

        $system = function () use ($command, $parameters) {
            return $command(...$parameters);
        };

        return \awaitable_process(function () use ($system) {
            return Kernel::addProcess($system, 3);
        });
    }

    /**
     * Sets access and modification time of file.
     * - This function needs to be prefixed with `yield`
     *
     * @param mixed $path
     * @param mixed|null $time
     * @param mixed|null $atime
     *
     * @return bool
     */
    function file_touch($path, $time = null, $atime = null)
    {
        return FileSystem::touch($path, $time, $atime);
    }

    /**
     * Renames a file or directory.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    function file_rename($from, $to)
    {
        return FileSystem::rename($from, $to);
    }

    /**
     * Deletes a file.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return bool
     */
    function file_unlink($path)
    {
        return FileSystem::unlink($path);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_link($from, $to)
    {
        return FileSystem::link($from, $to);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_symlink($from, $to, $flag = 0)
    {
        return FileSystem::symlink($from, $to, $flag);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_readlink($path)
    {
        return FileSystem::readlink($path);
    }

    /**
     * Attempts to create the directory specified by pathname.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param integer $mode
     * @param boolean $recursive
     *
     * @return bool
     */
    function file_mkdir($path, $mode = 0777, $recursive = false)
    {
        return FileSystem::mkdir($path, $mode, $recursive);
    }

    /**
     * Removes directory.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return bool
     */
    function file_rmdir($path)
    {
        return FileSystem::rmdir($path);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_chmod($filename, $mode)
    {
        return FileSystem::chmod($filename, $mode);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_chown($path, $uid, $gid)
    {
        return FileSystem::chown($path, $uid, $gid);
    }

    /**
     * List files and directories inside the specified path.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param mixed $flagSortingOrder
     *
     * @return array|bool
     */
    function file_scandir($path, $sortingOrder = 0)
    {
        return FileSystem::scandir($path, $sortingOrder);
    }

    /**
     * Gives information about a file.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param string $info
     * - Numeric    `$info` Description
     *````
     * 0    dev     device number
     * 1	ino	inode number
     * 2	mode	inode protection mode
     * 3	nlink	number of links
     * 4	uid	userid of owner
     * 5	gid	groupid of owner
     * 6	rdev	device type, if inode device
     * 7	size	size in bytes
     * 8	atime	time of last access (Unix timestamp)
     * 9	mtime	time of last modification (Unix timestamp)
     * 10	ctime	time of last inode change (Unix timestamp)
     * 11	blksize	blocksize of filesystem IO
     * 12	blocks	number of 512-byte blocks allocated
     *````
     * @return array|bool
     */
    function file_stat($path, $info = null)
    {
        return FileSystem::stat($path, $info);
    }

    /**
     * Return file size.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return int|bool
     */
    function file_size($path)
    {
        return \file_stat($path, 'size');
    }

    /**
     * Check if file exists.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return bool
     */
    function file_exist($path)
    {
        $status = yield \file_size($path);
        return \is_int($status);
    }

    /**
     * Turn `on/off` UV for file operations.
     *
     * @param bool $useUV
     * - `true` use **thread pool**.
     * - `false` use `child/subprocess`.
     */
    function file_operation(bool $useUV = false)
    {
        ($useUV === true) ? FileSystem::on() : FileSystem::off();
    }

    /**
     * Add and wait for result of an blocking `I/O` subprocess that runs in parallel.
     * This function turns the calling function internal __state/type__ used by `gather()`
     * to **process/paralleled** which is handled differently.
     *
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     *
     * @return mixed
     */
    function add_process($command, $timeout = 300, bool $display = false, $channel = null, $channelTask = null)
    {
        return Kernel::addProcess($command, $timeout, $display, $channel, $channelTask);
    }

    /**
     * Wrap the callable with `yield`, this insure the first attempt to execute will behave
     * like a generator function, will switch at least once without actually executing, return object instead.
     * This function is used by `away()` not really called directly.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args
     *
     * @return \Generator
     */
    function awaitable(callable $awaitableFunction, ...$args)
    {
        return yield yield $awaitableFunction(...$args);
    }

    /**
     * Wrap the a spawn `process` with `yield`, this insure the the execution
     * and return result is handled properly.
     * This function is used by `spawn_await()` not really called directly.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args
     *
     * @return \Generator
     */
    function awaitable_process(callable $awaitableFunction, ...$args)
    {
        return yield $awaitableFunction(...$args);
    }

    /**
     * Block/sleep for delay seconds.
     * Suspends the calling task, allowing other tasks to run.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#sleeping
     *
     * @param float $delay
     * @param mixed $result - If provided, it is returned to the caller when the coroutine complete
     */
    function sleep_for(float $delay = 0.0, $result = null)
    {
        return Kernel::sleepFor($delay, $result);
    }

    /**
     * Creates an communications Channel between coroutines.
     * Similar to Google Go language - basic, still needs additional functions
     * - This function needs to be prefixed with `yield`
     *
     * @return Channel $channel
     */
    function make()
    {
        return Kernel::make();
    }

    /**
     * Send message to an Channel
     * - This function needs to be prefixed with `yield`
     *
     * @param Channel $channel
     * @param mixed $message
     * @param int $taskId override send to different task, not set by `receiver()`
     */
    function sender(Channel $channel, $message = null, int $taskId = 0)
    {
        return Kernel::sender($channel, $message, $taskId);
    }

    /**
     * Set task as Channel receiver, and wait to receive Channel message
     * - This function needs to be prefixed with `yield`
     *
     * @param Channel $channel
     */
    function receiver(Channel $channel)
    {
        yield Kernel::receiver($channel);
        $message = yield Kernel::receive($channel);
        return $message;
    }

    /**
     * A goroutine is a function that is capable of running concurrently with other functions.
     * To create a goroutine we use the keyword `go` followed by a function invocation
     * - This function needs to be prefixed with `yield`
     *
     * @see https://www.golang-book.com/books/intro/10#section1
     *
     * @param callable $goFunction
     * @param mixed $args - if `generator`, $args can hold `customState`, and `customData`
     *
     * @return int task id
     */
    function go($goFunction, ...$args)
    {
        return Kernel::away($goFunction, ...$args);
    }

    /**
     * Wait for the callable to complete with a timeout.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#timeouts
     *
     * @param callable $callable
     * @param float $timeout
     */
    function wait_for($callable, float $timeout = 0.0)
    {
        return Kernel::waitFor($callable, $timeout);
    }

    /**
     * kill/remove an task using task id.
     * Optionally pass custom cancel state and error message for third party code integration.
     *
     * - This function needs to be prefixed with `yield`
     */
    function cancel_task(int $tid, $customState = null, string $errorMessage = 'Invalid task ID!')
    {
        return Kernel::cancelTask($tid, $customState, $errorMessage);
    }

    /**
     * Performs a clean application exit and shutdown.
     *
     * - This function needs to be prefixed with `yield`
     */
    function shutdown()
    {
        return Kernel::shutdown();
        \coroutine_clear();
    }

    /**
     * Returns the current context task ID
     *
     * - This function needs to be prefixed with `yield`
     *
     * @return int
     */
    function get_task()
    {
        return Kernel::getTask();
    }

    /**
     * Wait on read stream socket to be ready read from,
     * optionally schedule current task to execute immediately/next.
     *
     * - This function needs to be prefixed with `yield`
     */
    function read_wait($stream, bool $immediately = false)
    {
        return Kernel::readWait($stream, $immediately);
    }

    /**
     * Wait on write stream socket to be ready to be written to,
     * optionally schedule current task to execute immediately/next.
     *
     * - This function needs to be prefixed with `yield`
     */
    function write_wait($stream, bool $immediately = false)
    {
        return Kernel::writeWait($stream, $immediately);
    }

    /**
     * Wait on keyboard input.
     * Will not block other task on `Linux`, will continue other tasks until `enter` key is pressed,
     * Will block on Windows, once an key is typed/pressed, will continue other tasks `ONLY` if no key is pressed.
     * - This function needs to be prefixed with `yield`
     *
     * @return string
     */
    function input_wait(int $size = 256, bool $error = false)
    {
        return Coroutine::input($size, $error);
    }

    /**
     * Return the `string` of a variable type, or does a check, compared with string of the type.
     * Types are: `callable`, `string`, `int`, `float`, `null`, `bool`, `array`, `object`, or `resource`
     *
     * @return string|bool
     */
    function is_type($variable, string $comparedWith = null)
    {
        $checks = [
            'is_callable' => 'callable',
            'is_string' => 'string',
            'is_integer' => 'int',
            'is_float' => 'float',
            'is_null' => 'null',
            'is_bool' => 'bool',
            'is_array' => 'array',
            'is_object' => 'object',
            'is_resource' => 'resource',
        ];

        foreach ($checks as $func => $val) {
            if ($func($variable)) {
                return (empty($comparedWith)) ? $val : ($comparedWith == $val);
            }
        }

        // @codeCoverageIgnoreStart
        return 'unknown';
        // @codeCoverageIgnoreEnd
    }

    function coroutine_instance(): ?CoroutineInterface
    {
        global $__coroutine__;

        return $__coroutine__;
    }

    function coroutine_clear()
    {
        global $__coroutine__;
        $__coroutine__ = null;
        unset($GLOBALS['__coroutine__']);
    }

    function coroutine_create(\Generator $routine = null, ?string $driver = null)
    {
        $coroutine = \coroutine_instance();
        if (!$coroutine instanceof CoroutineInterface)
            $coroutine = new Coroutine($driver);

        if (!empty($routine))
            $coroutine->createTask($routine);

        return $coroutine;
    }

    /**
     * This function runs the passed coroutine, taking care of managing the scheduler and
     * finalizing asynchronous generators. It should be used as a main entry point for programs, and
     * should ideally only be called once.
     *
     * @see https://docs.python.org/3.8/library/asyncio-task.html#asyncio.run
     *
     * @param Generator $routine
     * @param string $driver event loop driver to use, either `auto`, `uv`, or `stream_select`
     */
    function coroutine_run(\Generator $routine = null, ?string $driver = 'auto')
    {
        $coroutine = \coroutine_create($routine, $driver);

        if ($coroutine instanceof CoroutineInterface) {
            $coroutine->run();
            return true;
        }
    }

    /**
     * Add something/callable to `coroutine` process pool
     *
     * @param callable $callable
     * @param int $timeout
     *
     * @return LauncherInterface
     */
    function parallel($callable, int $timeout = 300): LauncherInterface
    {
        $coroutine = \coroutine_instance();

        if ($coroutine instanceof CoroutineInterface)
            return $coroutine->addProcess($callable, $timeout);

        return \coroutine_create()->addProcess($callable, $timeout);
    }

    /**
     * Get/create process worker pool of an parallel instance.
     *
     * @return ParallelInterface
     */
    function parallel_pool(): ParallelInterface
    {
        $coroutine = \coroutine_instance();

        if ($coroutine instanceof CoroutineInterface)
            return $coroutine->getParallel();

        return \coroutine_create()->getParallel();
    }

    /**
     * Add something/callable to parallel instance process pool.
     *
     * @param callable $somethingToRun
     * @param int $timeout
     *
     * @return LauncherInterface
     */
    function parallel_add($somethingToRun, int $timeout = 300): LauncherInterface
    {
        return Processor::create($somethingToRun, $timeout);
    }

    /**
     * Execute process pool, wait for results. Will do other stuff come back later.
     *
     * @return array
     */
    function parallel_wait(): ?array
    {
        $pool = \parallel_pool();

        if ($pool instanceof ParallelInterface)
            return $pool->wait();
    }

    /**
     * Modeled as in `Go` Language. The behavior of defer statements is straightforward and predictable.
     * There are three simple rules:
     * 1. *A deferred function's arguments are evaluated when the defer statement is evaluated.*
     * 2. *Deferred function calls are executed in Last In First Out order after the* surrounding function returns.
     * 3. *Deferred functions can`t modify return values when is type, but can modify content of reference to array or object.*
     *
     * PHP Limitations:
     * - In this *PHP* defer implementation,
     *  you cant modify returned value. You can modify only content of returned reference.
     * - You must always set first parameter in `defer` function,
     *  the parameter MUST HAVE same variable name as other `defer`,
     *  and this variable MUST NOT exist anywhere in local scope.
     * - You can`t pass function declared in local scope by name to *defer*.
     *
     * Modified from https://github.com/tito10047/php-defer
     *
     * @see https://golang.org/doc/effective_go.html#defer
     *
     * @param Defer|null $previous defer
     * @param callable $callback
     * @param mixed ...$args
     *
     * @throws \Exception
     */
    function defer(&$previous, $callback)
    {
        $args = \func_get_args();
        \array_shift($args);
        \array_shift($args);
        Defer::deferring($previous, $callback, $args);
    }

    /**
     * Modeled as in `Go` Language. Regains control of a panicking `task`.
     *
     * Recover is only useful inside `defer()` functions. During normal execution, a call to recover will return nil
     * and have no other effect. If the current `task` is panicking, a call to recover will capture the value given
     * to panic and resume normal execution.
     *
     * @param Defer|null $previous defer
     * @param callable $callback
     * @param mixed ...$args
     */
    function recover(&$previous, $callback)
    {
        $args = \func_get_args();
        \array_shift($args);
        \array_shift($args);
        Defer::recover($previous, $callback, $args);
    }

    /**
     * Modeled as in `Go` Language.
     *
     * An general purpose function for throwing an Coroutine `Exception`,
     * or some abnormal condition needing to keep an `Task` stack trace.
     */
    function panic($message = '', $code = 0, \Throwable $previous = null)
    {
        throw new Panic($message, $code, $previous);
    }

    /**
     * An PHP Functional Programming Primitive.
     *
     * Return a curryied version of the given function. You can decide if you also
     * want to curry optional parameters or not.
     *
     * @see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#currying
     *
     * @param callable $function the function to curry
     * @param bool $required curry optional parameters ?
     * @return callable a curryied version of the given function
     */
    function curry(callable $function, $required = true)
    {
        if (\method_exists('Closure', 'fromCallable')) {
            $reflection = new \ReflectionFunction(\Closure::fromCallable($function));
        } else {
            if (\is_string($function) && \strpos($function, '::', 1) !== false) {
                $reflection = new \ReflectionMethod($function, null);
            } elseif (\is_array($function) && \count($function) === 2) {
                $reflection = new \ReflectionMethod($function[0], $function[1]);
            } elseif (\is_object($function) && \method_exists($function, '__invoke')) {
                $reflection = new \ReflectionMethod($function, '__invoke');
            } else {
                $reflection = new \ReflectionFunction($function);
            }
        }
        $count = $required ?
            $reflection->getNumberOfRequiredParameters() : $reflection->getNumberOfParameters();
        return \curry_n($count, $function);
    }

    /**
     * Return a version of the given function where the $count first arguments are curryied.
     *
     * No check is made to verify that the given argument count is either too low or too high.
     * If you give a smaller number you will have an error when calling the given function. If
     * you give a higher number, arguments will simply be ignored.
     *
     * @see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#curry_n
     *
     * @param int $count number of arguments you want to curry
     * @param callable $function the function you want to curry
     * @return callable a curryied version of the given function
     */
    function curry_n($count, callable $function)
    {
        $accumulator = function (array $arguments) use ($count, $function, &$accumulator) {
            return function (...$newArguments) use ($count, $function, $arguments, $accumulator) {
                $arguments = \array_merge($arguments, $newArguments);
                if ($count <= \count($arguments)) {
                    return \call_user_func_array($function, $arguments);
                }
                return $accumulator($arguments);
            };
        };
        return $accumulator([]);
    }
}
