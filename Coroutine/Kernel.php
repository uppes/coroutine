<?php

declare(strict_types=1);

namespace Async;

use Async\Spawn\Channeled;
use Async\Spawn\FutureInterface;
use Async\Channel;
use Async\CoroutineInterface;
use Async\TaskInterface;
use Async\Exceptions\LengthException;
use Async\Exceptions\InvalidStateError;
use Async\Exceptions\InvalidArgumentException;
use Async\Exceptions\TimeoutError;
use Async\Exceptions\CancelledError;
use Async\Spawn\ChanneledInterface;
use Async\FiberInterface;

/**
 * The Kernel
 * This class is used for Communication between the tasks and the scheduler
 *
 * The `yield` keyword in your code, act both as an interrupt and as a way to
 * pass information to (and from) the scheduler.
 */
final class Kernel
{
    protected $callback;
    protected static $gatherCount = 0;
    protected static $gatherShouldError = true;
    protected static $gatherShouldClearCancelled = true;

    /**
     * Custom `Gather` not started state.
     * @var string
     */
    protected static $isCustomSate = 'n/a';

    /**
     * Execute on already pre-completed `Gather` tasks.
     * @var callable
     */
    protected static $onPreComplete;

    /**
     * Execute on completed `Gather` tasks.
     * @var callable
     */
    protected static $onCompleted;

    /**
     * Execute on exception `Gather` tasks.
     * @var callable
     */
    protected static $onError;

    /**
     * Execute on cancelled `Gather` tasks.
     * @var callable
     */
    protected static $onCancel;

    /**
     * Execute on not started `Gather` tasks.
     * @var callable
     */
    protected static $onProcessing;

    /**
     * Execute cleanup on `GatherWait()` race tasks no longer needed.
     * @var callable
     */
    protected static $onClear;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Tells the scheduler to pass the calling `task` or `fiber`, and itself into the function.
     *
     * @param TaskInterface|FiberInterface $taskFiber
     * @param CoroutineInterface $coroutine
     * @return mixed
     */
    public function __invoke($taskFiber, CoroutineInterface $coroutine)
    {
        if ($taskFiber instanceof TaskInterface || $taskFiber instanceof FiberInterface) {
            $callback = $this->callback;
            return $callback($taskFiber, $coroutine);
        }

        // @codeCoverageIgnoreStart
        \panic('Must be instance of "Async\TaskInterface" or "Async\FiberInterface"');
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns the current context task ID
     *
     * @return int
     */
    public static function getTask()
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) {
                $task->sendValue($task->taskId());
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * Set current context Task to stateless, aka `networked`, meaning not storing any return values or exceptions on completion.
     * Not moved to completed task list.
     * Will return the current context task ID.
     *
     * - This function needs to be prefixed with `yield`
     *
     * @return int
     */
    public static function statelessTask()
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) {
                $task->taskType('networked');
                $task->sendValue($task->taskId());
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * Create an new task
     *
     * @return int task ID
     */
    public static function createTask(\Generator $coroutines)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($coroutines) {
                $task->sendValue($coroutine->createTask($coroutines));
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * Creates an Channel similar to Google's Go language
     *
     * @return object
     */
    public static function make()
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) {
                $task->sendValue(Channel::make($task, $coroutine));
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * Set Channel by caller's task, similar to Google Go language
     *
     * @param Channel $channel
     */
    public static function receiver(Channel $channel)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($channel) {
                $channel->receiver($task);
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * Wait to receive message, similar to Google Go language
     */
    public static function receive(Channel $channel)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($channel) {
                $channel->receive();
            }
        );
    }

    /**
     * Send an message to Channel by task id, similar to Google Go language
     *
     * @param mixed $message
     * @param int $taskId
     */
    public static function sender(Channel $channel, $message = null, int $taskId = 0)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($channel, $message, $taskId) {
                $target = $channel->receiverTask();
                $sender = $channel->senderTask();
                $targetTask = $target instanceof TaskInterface
                    ? $target
                    : $sender;

                $checkTask = $coroutine->taskInstance($taskId);
                if ($checkTask instanceof TaskInterface && $taskId > 0) {
                    $targetTask = $checkTask;
                }

                $targetTask->sendValue($message);
                $coroutine->schedule($targetTask);
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * kill/remove an task using task id.
     * Optionally pass custom cancel state and error message for third party code integration.
     *
     * @param int $tid
     * @param mixed $customState
     * @param string $errorMessage
     *
     * @throws \InvalidArgumentException
     */
    public static function cancelTask($tid, $customState = null, string $errorMessage = 'Invalid task ID!')
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($tid, $customState, $errorMessage) {
                if ($coroutine->cancelTask($tid, $customState)) {
                    $task->sendValue(true);
                    $coroutine->schedule($task);
                } else {
                    throw new InvalidArgumentException($errorMessage);
                }
            }
        );
    }

    /**
     * Performs a clean application exit and shutdown.
     *
     * Provide $skipTask incase called by an Signal Handler.
     *
     * @param int $skipTask - Defaults to the main parent task.
     * - The calling `$skipTask` task id will not get cancelled, the script execution will return to.
     * - Use `getTask()` to retrieve caller's task id.
     */
    public static function shutdown(int $skipTask = 1)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($skipTask) {
                $returnTask = $coroutine->taskInstance($skipTask);
                $coroutine->shutdown($skipTask);
                if ($returnTask instanceof TaskInterface) {
                    $coroutine->schedule($returnTask);
                }
            }
        );
    }

    /**
     * Wait on read stream/socket to be ready read from,
     * optionally schedule current task to execute immediately/next for third party code integration.
     *
     * @param resource $streamSocket
     * @param bool $immediately
     */
    public static function readWait($streamSocket, bool $immediately = false)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($streamSocket, $immediately) {
                $coroutine->addReader($streamSocket, $task);
                if ($immediately) {
                    $coroutine->schedule($task);
                }
            }
        );
    }

    /**
     * Wait on write stream/socket to be ready to be written to,
     * optionally schedule current task to execute immediately/next for third party code integration.
     *
     * @param resource $streamSocket
     * @param bool $immediately
     */
    public static function writeWait($streamSocket, bool $immediately = false)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($streamSocket, $immediately) {
                $coroutine->addWriter($streamSocket, $task);
                if ($immediately) {
                    $coroutine->schedule($task);
                }
            }
        );
    }

    /**
     * Block/sleep for delay seconds.
     * Suspends the calling task, allowing other tasks to run.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#sleeping
     *
     * @param float $delay
     * @param mixed $result - If provided, it is returned to the caller when the coroutine complete
     */
    public static function sleepFor(float $delay = 0.0, $result = null)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($delay, $result) {
                $coroutine->addTimeout(function () use ($task, $coroutine, $result) {
                    if (!empty($result))
                        $task->sendValue($result);
                    $coroutine->schedule($task);
                }, $delay);
            }
        );
    }

    /**
     * Add and wait for result of an blocking `I/O` subprocess that runs in parallel.
     * This function turns the calling function internal state/type used by `gather()`
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
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying `process` standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return mixed
     */
    public static function addProcess(
        $command,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null,
        $taskType = null
    ) {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine)
            use ($command, $timeout, $display, $channel, $channelTask, $signal, $signalTask, $taskType) {
                $task->taskType('paralleled');
                $task->setState('process');
                $task->customState($taskType);
                $Future = $coroutine->addProcess($command, $timeout, $display, $channel)
                    ->then(function ($result) use ($task, $coroutine) {
                        $coroutine->cancelProgress($task);
                        $task->setState('completed');
                        $task->sendValue($result);
                        $coroutine->schedule($task);
                        $coroutine->cancelProgress($task);
                    })
                    ->catch(function (\Throwable $error) use ($task, $coroutine) {
                        $coroutine->cancelProgress($task);
                        $task->setState('erred');
                        $task->setException(new \RuntimeException($error->getMessage()));
                        $coroutine->schedule($task);
                    })
                    ->timeout(function () use ($task, $coroutine, $timeout) {
                        $coroutine->cancelProgress($task);
                        $task->setState('cancelled');
                        $task->setException(new TimeoutError($timeout));
                        $coroutine->schedule($task);
                    });

                $task->customData($Future);

                if ($signal !== 0 && \is_int($signalTask)) {
                    $Future->signal($signal, function ($signaled)
                    use ($task, $coroutine, $signal, $signalTask) {
                        $coroutine->cancelProgress($task);
                        $task->setState('signaled');
                        $signaler = $coroutine->taskInstance($signalTask);
                        if ($signaler instanceof TaskInterface) {
                            $task->setException(new CancelledError('with signal: ' . $signal));
                            $signaler->sendValue($signaled);
                            $coroutine->schedule($signaler);
                        } else { // @codeCoverageIgnoreStart
                            $task->setException(new \Exception(\sprintf('An unhandled signal received: %s', $signal)));
                            $coroutine->schedule($task);
                        } // @codeCoverageIgnoreEnd
                    });
                }

                if ($channel instanceof Channeled && \is_int($channelTask)) {
                    $channel->setHandle($Future);
                    $task->customState([$channel, $channelTask]);
                    $Future->progress(function ($type, $data)
                    use ($coroutine, $channelTask) {
                        $ipcTask = $coroutine->taskInstance($channelTask);
                        if ($ipcTask instanceof TaskInterface) {
                            $ipcTask->sendValue([$type, $data]);
                            $coroutine->schedule($ipcTask);
                        }
                    });
                }
            }
        );
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
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying `process` standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return int
     */
    public static function spawnTask(
        $callable,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null,
        $taskType = 'yielded'

    ) {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine)
            use ($callable, $timeout, $display, $channel, $channelTask, $signal, $signalTask, $taskType) {
                $command = \awaitAble(function ()
                use ($callable, $timeout, $display, $channel, $channelTask, $signal, $signalTask, $taskType) {
                    $result = yield yield Kernel::addProcess(
                        $callable,
                        $timeout,
                        $display,
                        $channel,
                        $channelTask,
                        $signal,
                        $signalTask,
                        $taskType
                    );

                    return $result;
                });

                $task->sendValue($coroutine->createTask($command));
                $coroutine->schedule($task);
            }
        );
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
    public static function spawnKill(int $tid, int $signal = \SIGKILL)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($tid, $signal) {
                $spawnedTask = $coroutine->taskInstance($tid);
                if ($spawnedTask instanceof TaskInterface) {
                    $customData = $spawnedTask->getCustomData();
                    if ($customData instanceof FutureInterface) {
                        $customData->stop($signal);
                    }
                }

                $task->sendValue($coroutine->cancelTask($tid));
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * Add a signal handler for the signal, that's continuously monitored.
     * This function will return `int` immediately, use with `spawn_signal()`.
     * - The `$handler` function will be executed, if subprocess is terminated with the `signal`.
     * - Expect the `$handler` to receive `(int $signal)`.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3/library/signal.html#signal.signal
     *
     * @param int $signal
     * @param callable $handler
     *
     * @return int
     */
    public static function signalTask(int $signal, callable $handler)
    {
        return Kernel::away(function () use ($signal, $handler) {
            yield;
            while (true) {
                $trapSignal = yield;
                if ($signal === $trapSignal) {
                    return $handler($signal);
                }
            }
        });
    }

    /**
     * Add a file change event handler for the path being watched, that's continuously monitored.
     * This function will return `int` immediately, use with `monitor()`, `monitor_file()`, `monitor_dir()`.
     * - The `$handler` function will be executed every time theres activity with the path being watched.
     * - Expect the `$handler` to receive `(?string $filename, int $events, int $status)`.
     * - This function needs to be prefixed with `yield`
     *
     * @param callable $handler
     *
     * @return int
     */
    public static function monitorTask(callable $handler)
    {
        return Kernel::away(function () use ($handler) {
            yield;
            while (true) {
                $fileChanged = yield;
                if (\is_array($fileChanged) && (\count($fileChanged) == 3)) {
                    [$name, $event, $status] = $fileChanged;
                    $fileChanged = null;
                    yield $handler($name, $event, $status);
                }
            }
        });
    }

    /**
     * Add a progress handler for the subprocess, that's continuously monitored.
     * This function will return `int` immediately, use with `spawn_progress()`.
     * - The `$handler` function will be executed every time the subprocess produces output.
     * - Expect the `$handler` to receive `(string $type, $data)`, where `$type` is either `out` or `err`.
     * - This function needs to be prefixed with `yield`
     *
     * @param callable $handler
     *
     * @return int
     */
    public static function progressTask(callable $handler)
    {
        return Kernel::away(function () use ($handler) {
            yield;
            while (true) {
                $received = yield;
                if (\is_array($received) && (\count($received) == 2)) {
                    [$type, $data] = $received;
                    $received = null;
                    if (!\is_null($data))
                        yield $handler($type, $data);
                }
            }
        });
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
    public static function gatherWait(array $tasks, int $race = 0, bool $exception = true, bool $clear = true)
    {
        self::$gatherCount = $race;
        self::$gatherShouldError = $exception;
        self::$gatherShouldClearCancelled = $clear;
        return Kernel::gather(...$tasks);
    }

    /**
     * Allow passing custom functions to control how `gather()` react after task process state changes.
     * This is mainly used for third party integration without repeating `Gather`main functionality.
     *
     * @param string $isCustomSate - for custom status state to check on not stated tasks
     * @param null|callable $onPreComplete - for already finish tasks
     * @param null|callable $onProcessing - for not running tasks
     * @param null|callable $onCompleted - for finished tasks
     * @param null|callable $onError - for erring or failing tasks
     * @param null|callable $onCancel - for aborted cancelled tasks
     * @param null|callable $onClear - for cleanup on tasks not to be used any longer
     */
    public static function gatherController(
        string $isCustomSate = 'n/a',
        ?callable $onPreComplete = null,
        ?callable $onProcessing = null,
        ?callable $onCompleted = null,
        ?callable $onError = null,
        ?callable $onCancel = null,
        ?callable $onClear = null
    ): void {
        self::$isCustomSate = $isCustomSate;
        self::$onPreComplete = $onPreComplete;
        self::$onProcessing = $onProcessing;
        self::$onCompleted = $onCompleted;
        self::$onError = $onError;
        self::$onCancel = $onCancel;
        self::$onClear = $onClear;
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
     * @param int|array $taskId
     * @return array associative `$taskId` => `$result`
     */
    public static function gather(...$taskId)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($taskId) {
                $gatherCount = self::$gatherCount;
                $gatherShouldError = self::$gatherShouldError;
                $gatherShouldClearCancelled = self::$gatherShouldClearCancelled;
                self::$gatherCount = 0;
                self::$gatherShouldError = true;
                self::$gatherShouldClearCancelled = true;

                $isCustomSate = self::$isCustomSate;
                $onPreComplete = self::$onPreComplete;
                $onProcessing = self::$onProcessing;
                $onCompleted = self::$onCompleted;
                $onError = self::$onError;
                $onCancel = self::$onCancel;
                $onClear = self::$onClear;
                self::gatherController();

                $taskIdList = [];
                $isGatherListGenerator = false;
                $gatherIdList = (\is_array($taskId[0])) ? $taskId[0] : $taskId;
                foreach ($gatherIdList as $id => $value) {
                    if ($value instanceof \Generator) {
                        $isGatherListGenerator = true;
                        $id = $coroutine->createTask($value);
                        $taskIdList[$id] = $id;
                    } elseif (\is_int($value)) {
                        $taskIdList[$value] = $value;
                    } else {
                        \panic("Invalid access, only array of integers, or generator objects allowed!");
                    }
                }

                if ($isGatherListGenerator) {
                    $gatherIdList = \array_keys($taskIdList);
                }

                $results = [];
                $count = \count($taskIdList);
                $gatherSet = ($gatherCount > 0);
                if ($gatherSet) {
                    if ($count < $gatherCount) {
                        throw new LengthException(\sprintf('The (%d) tasks, not enough to fulfill the `race: (%d)` count!', $count, $gatherCount));
                    }
                }

                $taskList = $coroutine->currentTask();

                $completeList = $coroutine->completedTask();
                $countComplete = \count($completeList);
                $gatherCompleteCount = 0;
                $isResultsException = false;

                foreach ($gatherIdList as $index => $tid) {
                    if (isset($taskList[$tid]) || isset($completeList[$tid])) {
                        // @codeCoverageIgnoreStart
                        if (
                            isset($taskList[$tid])
                            && $taskList[$tid] instanceof TaskInterface
                            && $taskList[$tid]->isNetwork()
                        ) {
                            $count--;
                            $results[$tid] = null;
                            $gatherCompleteCount++;
                            unset($taskList[$tid]);
                            unset($taskIdList[$tid]);
                            unset($gatherIdList[$index]);
                        }
                        // @codeCoverageIgnoreEnd

                        continue;
                    } else {
                        $isResultsException = new InvalidStateError('Task ' . $tid . ' does not exists.');
                        if ($gatherShouldError) {
                            $countComplete = 0;
                            break;
                        } else {
                            $results[$tid] = $isResultsException;
                            $isResultsException = false;
                            unset($gatherIdList[$index]);
                        }
                    }
                }

                // Check and handle tasks already completed before entering/executing gather().
                if ($countComplete > 0) {
                    foreach ($completeList as $id => $tasks) {
                        if (isset($taskIdList[$id])) {
                            if (\is_callable($onPreComplete)) {
                                $result = $onPreComplete($tasks);
                            } else {
                                $result = $tasks->result();
                            }

                            if ($result instanceof \Throwable) {
                                $isResultsException = $result;
                            } else {
                                $results[$id] = $result;
                            }

                            $count--;
                            $gatherCompleteCount++;
                            unset($taskIdList[$id]);

                            // Update running task list.
                            self::updateList($coroutine, $id, $completeList);

                            // end loop, if gather race count reached
                            if ($gatherCompleteCount == $gatherCount)
                                break;
                        }
                    }
                }

                // Check and update base off gather race and completed count.
                if ($gatherSet) {
                    $subCount = ($gatherCount - $gatherCompleteCount);
                    if ($gatherCompleteCount != $gatherCount) {
                        $count = $subCount;
                    } elseif ($gatherCompleteCount == $gatherCount) {
                        $count = 0;
                    }
                }

                // Skip wait, just proceed to propagate/schedule the exception, if set.
                if ($gatherShouldError && ($isResultsException !== false)) {
                    $count = 0;
                }

                // Run and wait until race or count is reached.
                while ($count > 0) {
                    foreach ($taskIdList as $id) {
                        if (isset($taskList[$id])) {
                            $tasks = $taskList[$id];
                            // Handle if parallel task, check already completed or has not started.
                            if ($tasks->isParallel()) {
                                $completeList = $coroutine->completedTask();
                                if (isset($completeList[$id])) {
                                    $tasks = $completeList[$id];
                                    $tasks->setState('completed');
                                    $tasks->taskType('');
                                    continue;
                                }

                                // Handle if process not running, force run.
                                if ($tasks->isProcess()) {
                                    $type = $tasks->getCustomState();
                                    if (\is_string($type) && $type == 'signaling') {
                                        $coroutine->execute('signaling');
                                    } elseif (\is_string($type) && $type == 'yielded') {
                                        $coroutine->execute(true);
                                    } else {
                                        $coroutine->execute('channeling');
                                    }
                                }
                            }

                            // Handle if any other task not running/pending, force run.
                            if (
                                $tasks->isCustomState($isCustomSate)
                                || $tasks->isPending()
                                || $tasks->isRescheduled()
                            ) {
                                if (\is_callable($onProcessing)) {
                                    $onProcessing($tasks, $coroutine);
                                } else {
                                    try {
                                        if (($tasks->isPending() || $tasks->isRescheduled()) && $tasks->isCustomState(true)) {
                                            $tasks->customState();
                                            $coroutine->schedule($tasks);
                                            $tasks->run();
                                            continue;
                                        }

                                        if ($tasks->isPending()) {
                                            $coroutine->execute();
                                        } elseif ($tasks->isRescheduled()) {
                                            $coroutine->execute($tasks->getCycles() > 1);
                                        }
                                    } catch (\Throwable $error) {
                                        $tasks->setState(
                                            ($error instanceof CancelledError ? 'cancelled' : 'erred')
                                        );

                                        $tasks->setException($error);
                                    }
                                }
                                // Handle if task finished.
                            } elseif ($tasks->isCompleted()) {
                                if (\is_callable($onCompleted)) {
                                    $result = $onCompleted($tasks);
                                } else {
                                    $result = $tasks->result();
                                }

                                $count--;
                                unset($taskList[$id]);
                                self::updateList($coroutine, $id);
                                $results[$id] = $result;
                                // end loop, if set and race count reached
                                if ($gatherSet) {
                                    $subCount--;
                                    if ($subCount == 0)
                                        break;
                                }
                                // Handle if task erred or cancelled.
                            } elseif (
                                $tasks->isErred()
                                || $tasks->isCancelled()
                                || $tasks->isSignaled()
                            ) {
                                if ($tasks->isErred() && \is_callable($onError)) {
                                    $isResultsException = $onError($tasks);
                                } elseif ($tasks->isCancelled() && \is_callable($onCancel)) {
                                    $isResultsException = $onCancel($tasks);
                                } else {
                                    $isResultsException = $tasks->result();
                                }

                                $count--;
                                unset($taskList[$id]);
                                self::updateList($coroutine, $id, $taskList, $onClear, false, true);
                                // Check and propagate/schedule the exception.
                                if ($gatherShouldError) {
                                    $count = 0;
                                    break;
                                } else {
                                    $results[$id] = $isResultsException;
                                    $isResultsException = false;
                                }
                            }
                        }
                    }
                }

                // Check for, update and cancel/close any result not part of race gather count.
                if ($gatherSet && (\is_callable($onClear) || $gatherShouldClearCancelled) && ($isResultsException === false)) {
                    $resultId = \array_keys($results);
                    $abortList = \array_diff($gatherIdList, $resultId);
                    $currentList = $coroutine->currentTask();
                    $finishedList = $coroutine->completedTask();
                    foreach ($abortList as $id) {
                        if (isset($finishedList[$id])) {
                            // Update task list removing tasks already completed that will not be used, mark and execute any custom update/cancel routines
                            self::updateList($coroutine, $id, $finishedList, $onClear);
                        } elseif (isset($currentList[$id])) {
                            // Update task list removing current running tasks not part of race gather count, mark and execute any custom update, then cancel routine
                            self::updateList($coroutine, $id, $currentList, $onClear, true);
                        }
                    }
                }

                if ($gatherShouldError && ($isResultsException !== false)) {
                    $task->setException($isResultsException);
                } else {
                    $task->sendValue($results);
                }

                $coroutine->schedule($task);
            }
        );
    }

    /**
     * Update current/running task list, optionally call custom update function on the task.
     */
    protected static function updateList(
        CoroutineInterface $coroutine,
        int $taskId,
        array $completeList = [],
        ?callable $onClear = null,
        bool $cancel = false,
        bool $forceUpdate = false
    ): void {
        if (isset($completeList[$taskId]) && \is_callable($onClear)) {
            $onClear($completeList[$taskId]);
        }

        if ($cancel) {
            $coroutine->cancelTask($taskId);
        } else {
            if (empty($completeList) || $forceUpdate) {
                $completeList = $coroutine->completedTask();
            }

            if (isset($completeList[$taskId])) {
                unset($completeList[$taskId]);
            }

            $coroutine->updateCompletedTask($completeList);
        }
    }

    /**
     * Wait for the callable to complete with a timeout.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#timeouts
     *
     * @param callable $callable
     * @param float $timeout
     */
    public static function waitFor($callable, float $timeout = null)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($callable, $timeout) {
                if ($callable instanceof \Generator) {
                    $taskId = $coroutine->createTask($callable);
                } else {
                    $taskId = $coroutine->createTask(\awaitAble($callable));
                }

                $coroutine->addTimeout(function () use ($taskId, $timeout, $task, $coroutine) {
                    if (!empty($timeout)) {
                        $coroutine->cancelTask($taskId);
                        $task->setException(new TimeoutError($timeout));
                        $coroutine->schedule($task);
                    } else {
                        $completeList = $coroutine->completedTask();
                        if (isset($completeList[$taskId])) {
                            $tasks = $completeList[$taskId];
                            $result = $tasks->result();
                            self::updateList($coroutine, $taskId, $completeList);
                            $task->sendValue($result);
                        }
                        $coroutine->schedule($task);
                    }
                }, $timeout);
            }
        );
    }

    /**
     * Makes an resolvable function from label name that's callable with `away`
     * The passed in `function/callable/task` is wrapped to be `awaitAble`
     *
     * This will create closure function in global namespace with supplied name as variable
     *
     * @param string $labelFunction
     * @param Generator|callable $asyncFunction
     */
    public static function async(string $labelFunction, callable $asyncFunction)
    {
        $GLOBALS[$labelFunction] = function (...$args) use ($asyncFunction) {
            $return = yield $asyncFunction(...$args);
            return Coroutine::plain($return);
        };
    }

    /**
     * Add/schedule an `yield`-ing `function/callable/task` for execution.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
     *
     * @param Generator|callable $asyncLabel
     * @param mixed ...$args - if **$asyncLabel** is `Generator`, $args can hold `customState`, and `customData`
     * - for third party code integration.
     *
     * @return int $task id
     */
    public static function away($asyncLabel, ...$args)
    {
        $isLabel = false;
        if (!\is_array($asyncLabel) && !\is_callable($asyncLabel) && !$asyncLabel instanceof \Generator) {
            global ${$asyncLabel};
            $isLabel = isset(${$asyncLabel});
        }

        if ($isLabel && (${$asyncLabel}() instanceof \Generator)) {
            return Kernel::createTask(${$asyncLabel}(...$args));
        } else {
            return new Kernel(
                function ($task, CoroutineInterface $coroutine) use ($asyncLabel, $args) {
                    if ($asyncLabel instanceof \Generator) {
                        $tid = $coroutine->createTask($asyncLabel);
                        if (!empty($args)) {
                            $createdTask = $coroutine->taskInstance($tid);
                            if (($args[0] === 'true') || ($args[0] === true))
                                $createdTask->customState(true);
                            else
                                $createdTask->customState($args[0]);

                            if (isset($args[1])) {
                                $createdTask->customData($args[1]);
                            }
                        }

                        $task->sendValue($tid);
                    } else {
                        $task->sendValue($coroutine->createTask(\awaitAble($asyncLabel, ...$args)));
                    }

                    $coroutine->isFiber($task)
                        ? $coroutine->scheduleFiber($task)
                        : $coroutine->schedule($task);
                }
            );
        }
    }

    public static function suspendFiber($data)
    {
        return new Kernel(
            function (FiberInterface $fiber, CoroutineInterface $coroutine) use ($data) {
                $fiber->setState('suspended');
                $suspendTo = $fiber->getTaskFiber();
                $suspendTo->sendValue($data);
                $coroutine->isFiber($suspendTo)
                    ? $coroutine->scheduleFiber($suspendTo)
                    : $coroutine->schedule($suspendTo);
            }
        );
    }

    public static function startFiber(FiberInterface $fiber)
    {
        return new Kernel(
            function ($caller, CoroutineInterface $coroutine) use ($fiber) {
                $fiber->setTaskFiber($caller);
                $coroutine->scheduleFiber($fiber);
            }
        );
    }

    public static function resumeFiber(FiberInterface $fiber, $data)
    {
        return new Kernel(
            function ($caller, CoroutineInterface $coroutine) use ($fiber, $data) {
                $fiber->setState('rescheduled');
                $fiber->setTaskFiber($caller);
                $fiber->sendValue($data);
                $coroutine->scheduleFiber($fiber);
            }
        );
    }

    public static function throwFiber(FiberInterface $fiber, $exception)
    {
        return new Kernel(
            function ($caller, CoroutineInterface $coroutine) use ($fiber, $exception) {
                $fiber->setState('erred');
                $fiber->setTaskFiber($caller);
                $fiber->setException($exception);
                $coroutine->scheduleFiber($fiber);
            }
        );
    }
}
