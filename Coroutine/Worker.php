<?php

declare(strict_types=1);

namespace Async\Worker;

use Async\Spawn\Channeled;
use Async\Kernel;
use Async\Exceptions\Panic;

if (!\function_exists('awaitable_future')) {
    /**
     * Add/execute a blocking `I/O` `future` task that runs in parallel.
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show Future output
     * @param Channeled|resource|mixed|null $channel IPC/CSP communication to be passed to the underlying `Future` instance.
     * @param int|null $channelTask The task id to use for realtime **Channel** interaction.
     * @param int $signal
     * @param int $signalTask The task to call when Future is terminated with a signal.
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
     * - The `$handler` function will be executed, if `future` is terminated with the `signal`.
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
     * Add/execute a blocking `I/O` future task that runs in parallel.
     * Will execute the `$signalTask` task id, if `future` is terminated with the `$signal`.
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
     * @param bool $display set to show future output
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
     * Stop/kill a `future` with `signal`, and also `cancel` the task.
     * - This function needs to be prefixed with `yield`
     *
     * @param int $tid The task id of the future task.
     * @param int $signal `Termination/kill` signal constant.
     *
     * @return bool
     */
    function spawn_kill(int $tid, int $signal = \SIGKILL)
    {
        return Kernel::spawnKill($tid, $signal);
    }

    /**
     * Add a progress handler for the `future`, that's continuously monitored.
     * This function will return `int` immediately, use with `spawn_progress()`.
     * - The `$handler` function will be executed every time the `future` produces output.
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
     * Add/execute a blocking `I/O` future task that runs in parallel, but the `future` can be controlled.
     * The passed in `task id` can be use as a IPC handler for real time output interaction.
     *
     * The `$channelTask` will receive **output type** either(`out` or `err`),
     * and **the data/output** in real-time.
     *
     * Use: __Channel__ ->`write()` to write to the standard input of the `future`.
     *
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with yield
     *
     * @param mixed $command
     * @param Channeled|resource|mixed|null $channel IPC/CSP communication to be passed to the underlying `Future` instance.
     * @param int|null $channelTask The task id to use for realtime **future* output interaction.
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show `future` output
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
     * Add and wait for result of an blocking `I/O` `future` that runs in parallel.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show `future` output
     * @param Channeled|resource|mixed|null $channel IPC/CSP communication to be passed to the underlying `Future` instance.
     * @param int|null $channelTask The task id to use for realtime **future** output interaction.
     * @param int $signal
     * @param int $signalTask The task to call when `future` is terminated with a signal.
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
        return awaitable_future(function () use (
            $callable,
            $timeout,
            $display,
            $channel,
            $channelTask,
            $signal,
            $signalTask
        ) {
            return Kernel::addFuture($callable, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
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

        return awaitable_future(function () use ($system) {
            return Kernel::addFuture($system);
        });
    }

    /**
     * Add and wait for result of an blocking `I/O` future that runs in parallel.
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
     * @param bool $display set to show `future` output
     * @param Channeled|resource|mixed|null $channel IPC/CSP communication to be passed to the underlying `Future` instance.
     * @param int|null $channelTask The task id to use for realtime **future** output interaction.
     * @param int $signal
     * @param int $signalTask The task to call when `future` is terminated with a signal.
     *
     * @return mixed
     */
    function add_future(
        $command,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null
    ) {
        return Kernel::addFuture($command, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
    }

    /**
     * Wrap the a spawn `future` with `yield`, this insure the the execution
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
    function awaitable_future(callable $awaitableFunction, ...$args)
    {
        return yield $awaitableFunction(...$args);
    }
}
