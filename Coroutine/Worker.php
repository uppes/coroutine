<?php

declare(strict_types=1);

namespace Async\Worker;

use Async\Spawn\Channeled;
use Async\Coroutine\Kernel;
use Async\Coroutine\Exceptions\Panic;

if (!\function_exists('awaitable_process')) {
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
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return int
     */
    function spawn_task(
        $command,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null
    ) {
        return Kernel::spawnTask($command, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
    }

    /**
     * Add a signal handler for the signal, that's continuously monitored.
     * This function will return `int` immediately, use with `spawn_signal()`.
     * - The `$handler` function will be executed, if subprocess is terminated with the `signal`.
     * - Expect the `$handler` to receive `(int $signal)`.
     * - This function needs to be prefixed with yield
     *
     * @param int $signal
     * @param callable $handler
     *
     * @return int
     */
    function signal_task(int $signal, callable $handler)
    {
        return Kernel::signalTask($signal, $handler);
    }

    /**
     * Add/execute a blocking `I/O` subprocess task that runs in parallel.
     * Will execute the `$signalTask` task id, if subprocess is terminated with the `$signal`.
     *
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with yield
     *
     * @see https://docs.python.org/3/library/signal.html#module-signal
     *
     * @param callable|shell $command
     * @param int $signal
     * @param int|null $signalTask
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     *
     * @return int
     */
    function spawn_signal(
        $command,
        int $signal = 0,
        $signalTask = null,
        $timeout = 0,
        bool $display = false
    ) {
        return Kernel::spawnTask($command, $timeout, $display, null, null, $signal, $signalTask, 'signaling');
    }

    /**
     * Stop/kill a `child/subprocess` with `signal`, and also `cancel` the task.
     * - This function needs to be prefixed with `yield`
     *
     * @param int $tid The task id of the subprocess task.
     * @param int $signal `Termination/kill` signal constant.
     *
     * @return bool
     */
    function spawn_kill(int $tid, int $signal = \SIGKILL)
    {
        return Kernel::spawnKill($tid, $signal);
    }

    /**
     * Add a progress handler for the subprocess, that's continuously monitored.
     * This function will return `int` immediately, use with `spawn_progress()`.
     * - The `$handler` function will be executed every time the subprocess produces output.accordion
     * - Expect the `$handler` to receive `(string $type, $data)`, where `$type` is either `out` or `err`.
     * - This function needs to be prefixed with `yield`
     *
     * @param callable $handler
     *
     * @return int
     */
    function progress_task(callable $handler)
    {
        return Kernel::progressTask($handler);
    }

    /**
     * Add/execute a blocking `I/O` subprocess task that runs in parallel, but the subprocess can be controlled.
     * The passed in `task id` can be use as a IPC handler for real time interaction.
     *
     * The `$channelTask` will receive **output type** either(`out` or `err`),
     * and **the data/output** in real-time.
     *
     * Use: __Channel__ ->`send()` to write to the standard input of the process.
     *
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with yield
     *
     * @param mixed $command
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying `process` standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     *
     * @return int
     */
    function spawn_progress(
        $command,
        $channel = null,
        $channelTask = null,
        $timeout = 0,
        bool $display = false
    ) {
        return Kernel::spawnTask($command, $timeout, $display, $channel, $channelTask, 0, null);
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
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return mixed
     */
    function spawn_await(
        $callable,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null
    ) {
        return awaitable_process(function () use (
            $callable,
            $timeout,
            $display,
            $channel,
            $channelTask,
            $signal,
            $signalTask
        ) {
            return Kernel::addProcess($callable, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
        });
    }

    /**
     * Executes a blocking system call asynchronously in a **child/subprocess**.
     * By `proc_open`, or `uv_spawn` if **libuv** is loaded.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $command Any `PHP` builtin system operation command.
     * @param mixed ...$parameters
     *
     * @return  mixed
     * @throws Panic if not a callable.
     */
    function spawn_system(string $command, ...$parameters)
    {
        if (!\is_callable($command)) {
            \panic('Not a valid PHP callable command!');
        }

        // @codeCoverageIgnoreStart
        $system = function () use ($command, $parameters) {
            return @$command(...$parameters);
        };
        // @codeCoverageIgnoreEnd

        return awaitable_process(function () use ($system) {
            return Kernel::addProcess($system);
        });
        // @codeCoverageIgnoreStart
        //if (\is_base64($return)) {
        //    $check = \deserializer($return);
        //    $return = $check === false ? $return : $check;
        //}
        // @codeCoverageIgnoreEnd

        //return $return;
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
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return mixed
     */
    function add_process(
        $command,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null
    ) {
        return Kernel::addProcess($command, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
    }

    /**
     * Wrap the a spawn `process` with `yield`, this insure the the execution
     * and return result is handled properly.
     * - This function is used by `spawn_await()` shouldn't really be called directly.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args
     *
     * @return \Generator
     *
     * @internal
     */
    function awaitable_process(callable $awaitableFunction, ...$args)
    {
        return yield $awaitableFunction(...$args);
    }
}
