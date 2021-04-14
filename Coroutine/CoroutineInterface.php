<?php

namespace Async;

use Async\Spawn\Channeled;
use Async\Spawn\LauncherInterface;
use Async\Process;
use Async\TaskInterface;
use Async\ParallelInterface;
use Async\Exceptions\RuntimeException;
use Async\FiberInterface;

interface CoroutineInterface
{
    /**
     * Creates a new task (using the next free task id).
     * wraps coroutine into a Task and schedule its execution. Return the Task object/id.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#creating-tasks
     *
     * @param \Generator $coroutine
     * @return int task id
     */
    public function createTask(\Generator $coroutine);

    /**
     * Add an new task into the running task queue.
     *
     * @param TaskInterface $task
     */
    public function schedule(TaskInterface $task);

    /**
     * Add a fiber instance (using the next free task id).
     * The fiber added has been wrapped into a coroutine for the `tasks map` list and schedules its execution.
     *
     * @param FiberInterface $fiber
     * @return int fiber task id
     */
    public function addFiber(FiberInterface $fiber);

    /**
     * Add an fiber instance into the running task queue.
     *
     * @param FiberInterface $fiber
     */
    public function scheduleFiber(FiberInterface $fiber);

    /**
     * Check if `object` is a FiberInterface instance.
     *
     * @param mixed $fiber
     * @return boolean
     */
    public function isFiber($object);

    /**
     * Performs a clean application exit/shutdown, killing tasks/processes, and resetting all data.
     *
     * Provide `$skipTask` incase called by an Signal Handler.
     *
     * @param int $skipTask - Defaults to the main parent task.
     * - The calling `$skipTask` task id will not get cancelled, the script execution will return to.
     * - Use `getTask()` to retrieve caller's task id.
     */
    public function shutdown(int $skipTask = 1);

    /**
     * Reset all `Coroutine` data.
     */
    public function close();

    /**
     * kill/remove an subprocess progress `realtime` ipc handler task.
     *
     * @param TaskInterface $task
     *
     * @return void
     */
    public function cancelProgress(TaskInterface $task);

    /**
     * kill/remove an task using task id,
     * optionally pass custom cancel state for third party code integration.
     *
     * @param int $tid
     * @param mixed $customState
     * @return bool
     */
    public function cancelTask(int $tid, $customState = null);

    /**
     * Start the main supervisor task.
     * Process/walk the task `queue` and execute the tasks.
     * If a task is finished it's dropped, otherwise rescheduled at the end of the `queue`.
     * - The `task` that's finish with any `result`, is moved into an `completed` task list.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#running-an-asyncio-program
     */
    public function run();

    /**
     * Set main supervisor task running state to `false`.
     * This allows the supervisor task to be recreated.
     *
     * @return void
     */
    public function ioStop();

    /**
     * Adds a read `event/socket/stream/file` descriptor to start
     * monitoring for read availability and invoke callback
     * once it's available for reading.
     *
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.add_reader
     *
     * @param resource $stream
     * @param Fiber|Task|\Generator|Callable $task
     */
    public function addReader($stream, $task): CoroutineInterface;

    /**
     * Adds a write `event/socket/stream/file` descriptor to start
     * monitoring for write availability and invoke callback
     * once it's available for writing.
     *
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.add_writer
     *
     * @param resource $stream
     * @param Fiber|Task|\Generator|Callable $task
     */
    public function addWriter($stream, $task): CoroutineInterface;

    /**
     * Stop monitoring the `event/socket/stream/file` descriptor for read availability.
     *
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.remove_reader
     *
     * @param resource $stream
     */
    public function removeReader($stream): CoroutineInterface;

    /**
     * Stop monitoring the `event/socket/stream/file` descriptor for write availability.
     *
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.remove_writer
     * @param resource $stream
     */
    public function removeWriter($stream): CoroutineInterface;

    /**
     * Executes a function after x seconds.
     *
     * @param Task|\Generator|Callable $task
     * @param float $timeout
     */
    public function addTimeout($task, float $timeout);

    /**
     * Creates an object instance of the value which will signal to `Coroutine::create` that it's a return value.
     *
     *  - yield Coroutine::value("I'm a return value!");
     *
     * @internal
     *
     * @param mixed $value
     * @return ReturnValueCoroutine
     */
    public static function value($value);

    /**
     * Creates an object instance of the value which will signal to `Coroutine::create` that it's a return value.
     *
     * @internal
     *
     * @param mixed $value
     * @return PlainValueCoroutine
     */
    public static function plain($value);

    /**
     * Return the currently running/pending task list.
     *
     * @internal
     *
     * @return array|null
     */
    public function currentTask(): ?array;

    /**
     * Return list of completed tasks, which the **results** has not been retrieved using `gather()`.
     *
     * @internal
     *
     * @return array|null
     */
    public function completedTask(): ?array;

    /**
     * Update completed tasks, used by `gather()`.
     *
     * @internal
     *
     * @return void
     */
    public function updateCompletedTask();

    /**
     * Return the `Task` instance reference by `int` task id.
     *
     * @param int $taskId
     *
     * @internal
     *
     * @return null|TaskInterface
     */
    public function taskInstance(int $taskId = 0): ?TaskInterface;

    /**
     * Add callable for parallel processing, in an separate php process
     *
     * @see https://docs.python.org/3.8/library/asyncio-subprocess.html#creating-subprocesses
     *
     * @param callable $callable
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     *
     * @return LauncherInterface
     */
    public function addProcess($callable, int $timeout = 0, bool $display = false, $channel = null): LauncherInterface;

    /**
     * There are no **UV** file system operations/events pending.
     *
     * @return bool
     */
    public function isFsEmpty(): bool;

    /**
     * Add a UV file system operation to counter.
     *
     * @return void
     */
    public function fsAdd(): void;

    /**
     * Remove a UV file system operation from counter.
     *
     * @return void
     */
    public function fsRemove(): void;

    /**
     * There are no **UV** network operations pending.
     *
     * @return bool
     */
    public function isIoEmpty(): bool;

    /**
     * Add a UV network operation to counter.
     *
     * @return void
     */
    public function ioAdd(): void;

    /**
     * Remove a UV network operation from counter.
     *
     * @return void
     */
    public function ioRemove(): void;

    /**
     * Return the `Coroutine` class `libuv` loop handle, otherwise throw exception, if enabled and no driver found.
     *
     * @return null|\UVLoop
     * @throws RuntimeException
     */
    public function getUV(): ?\UVLoop;

    /**
     * Is `libuv` features available.
     */
    public function isUv(): bool;

    /**
     * Setup to use `libuv` features, reset/recreate **UV** handle, enable/disable.
     * - This will `stop` and `delete` any current **UV** event loop instance.
     * - This will also reset `FileSystem::setup` and **symplely/spawn** `Spawn::setup`
     * with the same config.
     *
     * @param bool $useUvLoop
     *
     * @return CoroutineInterface
     */
    public function setup(bool $useUvLoop = true): CoroutineInterface;

    /**
     * The `Process` class manager instance for Blocking I/O.
     *
     * @param callable|null $timedOutCallback
     * @param callable|null $finishCallback
     * @param callable|null $failCallback
     * @return Process
     */
    public function getProcess(
        ?callable $timedOutCallback = null,
        ?callable $finishCallback = null,
        ?callable $failCallback = null,
        ?callable $signalCallback  = null
    ): Process;

    /**
     * The `Parallel` class pool process instance.
     *
     * @return ParallelInterface
     */
    public function getParallel(): ParallelInterface;

    /**
     * Is `libuv` features available and the system is **Linux**.
     *
     * `Note:` Network related 'libuv` features are currently broken on **Windows**.
     *
     * @return bool
     */
    public function isUvActive(): bool;

    /**
     * Check if `PCNTL` extension is available for asynchronous signaling.
     *
     * @return bool
     */
    public function isPcntl(): bool;

    /**
     * Run all `tasks` in the queue.
     *
     * If there are none, no I/O, or timers the script/application will exit immediately.
     *
     * @internal
     *
     * @param bool $isReturn - should return to caller after one loop tick, this set by `gather()`
     */
    public function execute($isReturn = false);

    /**
     * Execute/schedule the retrieved `$task`.
     *
     * @internal
     *
     * @param Task|\Generator|Callable $task
     * @param mixed $parameters
     */
    public function executeTask($task, $parameters = null);

    /**
     * Create and manage a stack of nested coroutine calls. This allows turning
     * regular functions/methods into sub-coroutines just by yielding them.
     *
     *  - $value = (yield functions/methods($foo, $bar));
     *
     * @internal
     *
     * @param \Generator $gen
     */
    public static function create(\Generator $gen);

    /**
     * Register a listener to be notified when a signal has been caught by this process.
     *
     * This is useful to catch user interrupt signals or shutdown signals from the `OS`.
     *
     * The listener callback function MUST be able to accept a single parameter,
     * the signal added by this method or you MAY use a function which
     * has no parameters at all.
     *
     * **Note: A listener can only be added once to the same signal, any
     * attempts to add it more than once will be ignored.**
     *
     * @param int $signal
     * @param Task|\Generator|Callable $listener
     */
    public function addSignal($signal, $listener);

    /**
     * Removes a previously added signal listener.
     *
     * Any attempts to remove listeners that aren't registered will be ignored.
     *
     * @param int $signal
     * @param Task|\Generator|Callable $listener
     */
    public function removeSignal($signal, $listener);
}
