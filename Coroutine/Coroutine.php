<?php

declare(strict_types=1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Task;
use Async\Coroutine\Parallel;
use Async\Coroutine\ParallelInterface;
use Async\Coroutine\Process;
use Async\Coroutine\Signaler;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\ReturnValueCoroutine;
use Async\Coroutine\PlainValueCoroutine;
use Async\Coroutine\CoroutineInterface;
use Async\Spawn\Channel;
use Async\Spawn\Spawn;
use Async\Spawn\LauncherInterface;
use Async\Coroutine\Exceptions\CancelledError;
use Async\Coroutine\Exceptions\InvalidArgumentException;

/**
 * The Scheduler
 *
 * @see https://docs.python.org/3/library/asyncio-task.html#coroutines
 */
final class Coroutine implements CoroutineInterface
{
    /**
     * a task's unique id number
     *
     * @var int
     */
    protected $maxTaskId = 0;

    /**
     * List of currently running tasks
     *
     * @var array[] taskId => task
     */
    protected $taskMap = [];

    /**
     * List of completed tasks
     *
     * @var array
     */
    protected $completedMap = [];

    /**
     * Queue of `Task`, holding all created `coroutines/generators`
     *
     * @var \SplQueue
     */
    protected $taskQueue;

    /**
     * A list of timers, or **UV** timer handles, added by `addTimeout`.
     *
     * @var array
     */
    protected $timers = [];

    /**
     * Combined list of readable `id` of socket/streams/events, and read callbacks.
     *
     * @var resource[] [id, tasks]
     */
    protected $waitingForRead = [];

    /**
     * Combined list of writable `id` of socket/streams/events, and write callbacks.
     *
     * @var resource[] [id, tasks]
     */
    protected $waitingForWrite = [];

    /**
     * The **UV** event loop instance,
     * If not set, will use PHP built-in `stream_select`
     *
     * @var \UVLoop
     */
    protected $uv;

    /**
     * The **UV** Stream/Socket/FD event callback
     *
     * @var callable
     */
    protected $onEvent;

    /**
     * The **UV** timer event callback
     *
     * @var callable
     */
    protected $onTimer;

    /**
     * The **UV** signal event callback
     *
     * @var callable
     */
    protected $onSignal;

    /**
     * Check for `libuv` UV Signal feature, mainly for Windows.
     *
     * @var bool
     */
    protected $isUvSignal;

    /**
     * Check/counter for `libuv` UV File System feature.
     *
     * @var int
     */
    protected $uvFileSystem = 0;

    /**
     * Status to control general use of `libuv` features.
     *
     * @var bool
     */
    protected $useUv = false;

    /**
     * list of **UV** event handles, added by `addReader`, `addWriter`
     *
     * @var \UV[]
     */
    protected $events = [];

    /**
     * list of **UV** signal handles, added by ``, ``
     *
     * @var \UVSignal[]
     */
    protected $signals = [];

    /**
     * @var Process
     */
    protected $process = null;

    /**
     * @var Parallel
     */
    protected $parallel;

    /**
     * @var Signaler
     */
    protected $signaler;

    /**
     * Check for prefer high-resolution timer, available as of PHP 7.3+
     *
     * @var bool
     */
    protected $isHighTimer;

    public function __destruct()
    {
        $this->shutdown(0);
        unset($this->taskQueue);
        $this->taskQueue = null;
    }

    public function close()
    {
        if ($this->uv instanceof \UVLoop) {
            @\uv_stop($this->uv);
            @\uv_loop_delete($this->uv);
        }

        if ($this->parallel instanceof ParallelInterface) {
            $this->parallel->close();
        }

        $this->uv = null;
        $this->parallel = null;
        unset($this->process);
        $this->process = null;
        unset($this->signaler);
        $this->signaler = null;
        $this->onEvent = null;
        $this->onTimer = null;
        $this->onSignal = null;
        $this->isUvSignal = null;
        $this->isHighTimer = null;
        $this->maxTaskId = 0;
        $this->uvFileSystem = 0;
        $this->useUv = false;
        $this->taskMap = [];
        $this->completedMap = [];
        $this->timers = [];
        $this->waitingForRead = [];
        $this->waitingForWrite = [];
        $this->events = [];
        $this->signals = [];
    }

    /**
     * This scheduler will detect if the [`ext-uv` PECL extension](https://pecl.php.net/package/uv) is
     * installed, which provides an interface to `libuv` library. An native like event loop engine.
     *
     * @param string|null $driver set event loop to use, override detection.
     *
     * @see https://github.com/bwoebi/php-uv
     */
    public function __construct(?string $driver = 'auto')
    {
        global $__coroutine__;
        $__coroutine__ = $this;
        $this->initSignals();

        if (\in_array($driver, ['auto', 'uv']) && \function_exists('uv_loop_new')) {
            $this->uv = \uv_loop_new();

            Spawn::setup($this->uv);

            // @codeCoverageIgnoreStart
            $this->onEvent = function ($event, $status, $events, $stream) {
                if ($status !== 0) {
                    $this->pollEvent($stream);
                    if ($events === 0) {
                        $events = \UV::READABLE | \UV::WRITABLE;
                    }
                }

                if (isset($this->waitingForRead[(int) $stream]) && ($events & \UV::READABLE)) {
                    $this->updateScheduler('read', $stream);
                }

                if (isset($this->waitingForWrite[(int) $stream]) && ($events & \UV::WRITABLE)) {
                    $this->updateScheduler('write', $stream);
                }
            };

            $this->onTimer = function ($timer) {
                $taskTimer = $this->timers[(int) $timer];
                @\uv_timer_stop($timer);
                \uv_unref($timer);
                unset($this->timers[(int) $timer]);
                $this->executeTask($taskTimer[1], $timer);
            };
            // @codeCoverageIgnoreEnd
        }

        $this->isHighTimer = \function_exists('hrtime');
        $this->parallel = new Parallel($this);
        $this->taskQueue = new \SplQueue();
    }

    protected function timestamp()
    {
        return (float) ($this->isHighTimer ? \hrtime(true) / 1e+9 : \microtime(true));
    }

    /**
     * @codeCoverageIgnore
     */
    protected function addEvent($stream)
    {
        if (!isset($this->events[(int) $stream])) {
            $meta = \stream_get_meta_data($stream);
            switch ($meta['stream_type'] ?? '') {
                case 'STDIO':
                    if ($meta['wrapper_type'] == 'plainfile') {

                        break;
                    }
                case 'tcp_socket/ssl':
                    $this->events[(int) $stream] = \uv_poll_init($this->uv, $stream);
                    break;
                default:
                    $this->events[(int) $stream] = \uv_poll_init_socket($this->uv, $stream);
            }
        }

        if ($this->events[(int) $stream] !== false) {
            $this->pollEvent($stream);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function removeReadEvent($stream)
    {
        if (!isset($this->events[(int) $stream])) {
            return;
        }

        if (isset($this->waitingForRead[(int) $stream])) {
            \uv_poll_stop($this->events[(int) $stream]);
            \uv_close($this->events[(int) $stream]);
            unset($this->events[(int) $stream]);
            return;
        }

        $this->pollEvent($stream);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function removeWriteEvent($stream)
    {
        if (!isset($this->events[(int) $stream])) {
            return;
        }

        if (isset($this->waitingForWrite[(int) $stream])) {
            \uv_poll_stop($this->events[(int) $stream]);
            \uv_close($this->events[(int) $stream]);
            unset($this->events[(int) $stream]);
            return;
        }

        $this->pollEvent($stream);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function pollEvent($stream)
    {
        if (!isset($this->events[(int) $stream])) {
            return;
        }

        $flags = 0;
        if (isset($this->waitingForRead[(int) $stream])) {
            $flags |= \UV::READABLE;
        }

        if (isset($this->waitingForWrite[(int) $stream])) {
            $flags |= \UV::WRITABLE;
        }

        \uv_poll_start($this->events[(int) $stream], $flags, $this->onEvent);
    }

    public function fsCount(): int
    {
        return $this->uvFileSystem;
    }

    public function fsAdd(): void
    {
        $this->uvFileSystem++;
    }

    public function fsRemove(): void
    {
        $this->uvFileSystem--;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setup(bool $useUvLoop = true)
    {
        $this->useUv = $useUvLoop;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getUV(): ?\UVLoop
    {
        if ($this->uv instanceof \UVLoop)
            return $this->uv;
        elseif (\function_exists('uv_default_loop'))
            return \uv_default_loop();

        if ($this->useUv)
            throw new \RuntimeException('Calling method when "libuv" driver not loaded!');

        return null;
    }

    public function getParallel(): ParallelInterface
    {
        return $this->parallel;
    }

    public function getProcess(
        ?callable $timedOutCallback = null,
        ?callable $finishCallback = null,
        ?callable $failCallback = null,
        ?callable $signalCallback  = null
    ): Process {
        if (!empty($this->process)) {
            $this->process->stopAll();
            $this->process = null;
        }

        $this->process = new Process($this, $timedOutCallback, $finishCallback, $failCallback, $signalCallback );
        return $this->process;
    }

    public function addProcess($callable, int $timeout = 0, bool $display = false, $channel = null): LauncherInterface
    {
        $launcher = $this->parallel->add($callable, $timeout, $channel);

        return $display ? $launcher->displayOn() : $launcher;
    }

    /**
     * @codeCoverageIgnore
     */
    public function isUv(): bool
    {
        return $this->useUv;
    }

    public function isUvActive(): bool
    {
        return ($this->uv instanceof \UVLoop) && $this->useUv;
    }

    public function isPcntl(): bool
    {
        return \extension_loaded('pcntl')
            && \function_exists('pcntl_async_signals')
            && \function_exists('posix_kill');
    }

    public function createTask(\Generator $coroutine)
    {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    public function schedule(TaskInterface $task)
    {
        $this->taskQueue->enqueue($task);
    }

    /**
     * A `stream/socket/fd` or `event` is free, ready or has data.
     * Retrieve `Task`, remove and update scheduler for it's execution.
     *
     * @param string $type `read` or `write`
     * @param mixed $stream
     */
    protected function updateScheduler(string $type, $stream)
    {
        if ($type == 'read') {
            list(, $tasks) = $this->waitingForRead[(int) $stream];
            $this->removeReader($stream);

            foreach ($tasks as $task) {
                $this->executeTask($task, $stream);
            }
        } elseif ($type == 'write') {
            list(, $tasks) = $this->waitingForWrite[(int) $stream];
            $this->removeWriter($stream);

            foreach ($tasks as $task) {
                $this->executeTask($task, $stream);
            }
        }
    }

    public function executeTask($task, $parameters = null)
    {
        if ($task instanceof TaskInterface) {
            $this->schedule($task);
        } elseif ($task($parameters) instanceof \Generator) {
            $this->createTask($task($parameters));
        }
    }

    public function shutdown(int $skipTask = 1)
    {
        if (!empty($this->process))
            $this->process->stopAll();

        if (!empty($this->taskMap)) {
            $map = \array_reverse($this->taskMap, true);
            $keys = \array_keys($map);
            foreach ($keys as $id) {
                if ($id !== $skipTask && $id > 0) {
                    $this->cancelTask((int) $id);
                }
            }
        }

        if (!empty($this->completedMap)) {
            foreach ($this->completedMap as $task) {
                $task->close();
                $task->customState('shutdown');
            }
        }

        // @codeCoverageIgnoreStart
        if ($this->isUvActive()) {
            \uv_stop($this->uv);

            foreach ($this->timers as $timer) {
                if ($timer instanceof \UVTimer && \uv_is_active($timer))
                    \uv_timer_stop($timer);
            }

            foreach ($this->signals as $signal) {
                if ($signal instanceof \UVSignal && \uv_is_active($signal))
                    \uv_signal_stop($signal);
            }

            foreach ($this->events as $event) {
                if ($event instanceof \UV && \uv_is_active($event))
                    \uv_close($event);
            }

            \uv_run($this->uv);
        }
        // @codeCoverageIgnoreEnd

        $this->close();
    }

    public function cancelTask(int $tid, $customState = null)
    {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }

        unset($this->taskMap[$tid]);

        foreach ($this->taskQueue as $i => $task) {
            if ($task->taskId() === $tid) {
                $task->close();
                if (!empty($customState))
                    $task->customState($customState);

                $task->setState('cancelled');
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }

    public function cancelProgress(TaskInterface $task)
    {
        $channel = $task->getCustomState();
        if (\is_array($channel) && (\count($channel) == 2)) {
            [$channel, $channelTask] = $channel;
            if ($channel instanceof Channel && \is_int($channelTask) && isset($this->taskMap[$channelTask])) {
                unset($this->taskMap[$channelTask]);
                foreach ($this->taskQueue as $i => $task) {
                    if ($task->taskId() === $channelTask) {
                        $task->close();
                        $task->setState('cancelled');
                        unset($this->taskQueue[$i]);
                        break;
                    }
                }
            }
        }
    }

    public function currentTask(): ?array
    {
        if (!isset($this->taskMap)) {
            return null;
        }

        return $this->taskMap;
    }

    public function completedTask(): ?array
    {
        if (!isset($this->completedMap)) {
            return null;
        }

        return $this->completedMap;
    }

    public function updateCompletedTask($taskMap = [])
    {
        $this->completedMap = $taskMap;
    }

    public function run()
    {
        $this->createTask($this->ioWaiting());
        return $this->execute();
    }

    public function execute($isReturn = false)
    {
        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $task->setState('running');
            $task->cyclesAdd();
            $value = $task->run();

            if ($value instanceof Kernel) {
                try {
                    $value($task, $this);
                } catch (\Throwable $error) {
                    $this->cancelProgress($task);
                    $task->setState(
                        ($error instanceof CancelledError ? 'cancelled' : 'erred')
                    );

                    $task->setException($error);
                    $this->schedule($task);
                }

                continue;
            }

            if ($task->isFinished()) {
                $this->cancelProgress($task);
                $task->setState('completed');
                $id = $task->taskId();
                $this->completedMap[$id] = $task;
                unset($this->taskMap[$id]);
            } else {
                $task->setState('rescheduled');
                $this->schedule($task);
            }

            if ($isReturn) {
                return;
            }
        }
    }

    /**
     * Runs all pending timers.
     *
     * @return int|void
     *
     * @codeCoverageIgnore
     */
    protected function runTimers()
    {
        if ($this->isUvActive()) {
            return (\count($this->timers) > 0) ? 1 : false;
        }

        $now = $this->timestamp();
        while (($timer = \array_pop($this->timers)) && $timer[0] < $now) {
            $this->executeTask($timer[1]);
        }

        // Add the last timer back to the array.
        if ($timer) {
            $this->timers[] = $timer;

            return \max(0, $timer[0] - $this->timestamp());
        }
    }

    /**
     * Check for I/O events, streams/sockets/fd activity and `yield`,
     * will exit if nothing is pending.
     */
    protected function ioWaiting()
    {
        while (true) {
            if (
                $this->taskQueue->isEmpty()
                && empty($this->waitingForRead)
                && empty($this->waitingForWrite)
                && empty($this->timers)
                && $this->process->isEmpty()
                && !$this->isSignaling()
                && ($this->fsCount() == 0)
            ) {
                break;
            } else {
                $streamWait = null;
                $this->process->processing();

                $nextTimeout = $this->runTimers();
                if (\is_numeric($nextTimeout))
                    // Wait until the next Timeout should trigger.
                    $streamWait = $nextTimeout * 1000000;
                elseif (!$this->taskQueue->isEmpty())
                    // There's a pending 'createTask'. Don't wait.
                    $streamWait = 0;
                elseif (!$this->process->isEmpty())
                    // There's a running 'process', wait some before rechecking.
                    $streamWait = $this->process->sleepingTime();

                if ($this->isUvActive()) {
                    \uv_run($this->uv, $streamWait ? \UV::RUN_ONCE : \UV::RUN_NOWAIT);
                } else {
                    if ($this->uv instanceof \UVLoop) {
                        \uv_run($this->uv, \UV::RUN_NOWAIT);
                    }

                    $this->ioSocketStream($streamWait);
                }

                yield;
            }
        }
    }

    /**
     * Wait for activity, or until the next timer is due.
     *
     * @param integer|null $timeout microseconds, or null to wait forever.
     */
    protected function ioSocketStream($timeout)
    {
        if (empty($this->waitingForRead) && empty($this->waitingForWrite)) {
            return;
        }

        $rSocks = [];
        foreach ($this->waitingForRead as list($socket)) {
            $rSocks[] = $socket;
        }

        $wSocks = [];
        foreach ($this->waitingForWrite as list($socket)) {
            $wSocks[] = $socket;
        }

        $eSocks = []; // dummy
        if (!@\stream_select(
            $rSocks,
            $wSocks,
            $eSocks,
            (null === $timeout) ? null : 0,
            $timeout ? (int) ($timeout * (($timeout === null) ? 1000000 : 1)) : 0
        )) {
            return;
        }

        foreach ($rSocks as $socket) {
            $this->updateScheduler('read', $socket);
        }

        foreach ($wSocks as $socket) {
            $this->updateScheduler('write', $socket);
        }
    }

    public function addReader($stream, $task): CoroutineInterface
    {
        $already = true;
        if (isset($this->waitingForRead[(int) $stream])) {
            $already = false;
            $this->waitingForRead[(int) $stream][1][] = $task;
        } else {
            $this->waitingForRead[(int) $stream] = [$stream, [$task]];
        }

        if ($this->isUvActive() && $already)
            $this->addEvent($stream);

        return $this;
    }

    public function addWriter($stream, $task): CoroutineInterface
    {
        $already = true;
        if (isset($this->waitingForWrite[(int) $stream])) {
            $already = false;
            $this->waitingForWrite[(int) $stream][1][] = $task;
        } else {
            $this->waitingForWrite[(int) $stream] = [$stream, [$task]];
        }

        if ($this->isUvActive() && $already)
            $this->addEvent($stream);

        return $this;
    }

    public function removeReader($stream): CoroutineInterface
    {
        if ($this->isUvActive()) {
            $this->removeReadEvent($stream);
        }

        unset($this->waitingForRead[(int) $stream]);

        return $this;
    }

    public function removeWriter($stream): CoroutineInterface
    {
        if ($this->isUvActive()) {
            $this->removeWriteEvent($stream);
        }

        unset($this->waitingForWrite[(int) $stream]);

        return $this;
    }

    public function addSignal($signal, $listener)
    {
        if (!$this->signaler)
            return;

        $first = $this->signaler->count($signal) === 0;
        $this->signaler->add($signal, $listener);

        if ($first && $this->isPcntl()) {
            \pcntl_signal($signal, array($this->signaler, 'execute'));
        } elseif ($this->isUvActive() || $this->isUvSignal) {
            if (!isset($this->signals[$signal])) {
                $signals = $this->signaler;
                $this->signals[$signal] = \uv_signal_init($this->isUvActive() ? $this->uv : \uv_default_loop());
                \uv_signal_start($this->signals[$signal], function ($signal, $signalInt) use ($signals) {
                    $signals->execute($signalInt);
                }, $signal);
            }
        }
    }

    public function removeSignal($signal, $listener)
    {
        if (!$this->signaler || !$this->signaler->count($signal))
            return;

        $this->signaler->remove($signal, $listener);

        if ($this->signaler->count($signal) === 0 && $this->isPcntl()) {
            \pcntl_signal($signal, \SIG_DFL);
        } elseif ($this->isUvActive() || $this->isUvSignal) {
            if (isset($this->signals[$signal]) && $this->signaler->count($signal) === 0) {
                //\uv_signal_stop($this->signals[$signal]);
                unset($this->signals[$signal]);
            }
        }
    }

    /**
     * Setup Signal listener.
     */
    public function initSignals()
    {
        $this->isUvSignal = \function_exists('uv_loop_new');
        if (empty($this->signaler) && ($this->isPcntl() || $this->isUvActive() || $this->isUvSignal)) {
            $this->signaler = new Signaler($this);

            if ($this->isPcntl()) {
                $this->isUvSignal = false;
                \pcntl_async_signals(true);
            }
        }
    }

    public function isSignaling()
    {
        if (!$this->signaler)
            return;

        return !$this->signaler->isEmpty();
    }

    public function getSignaler()
    {
        return $this->signaler;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function addTimer($interval, $callback)
    {
        $timer = \uv_timer_init($this->uv);
        $this->timers[(int) $timer] = [$interval, $callback];
        \uv_timer_start(
            $timer,
            (($interval < 0) ? 0 : \floor($interval * 1000)),
            0,
            $this->onTimer
        );
    }

    public function addTimeout($task, float $timeout)
    {
        if ($this->isUvActive()) {
            return $this->addTimer($timeout, $task);
        }

        $triggerTime = $this->timestamp() + ($timeout);
        if (!$this->timers) {
            // Special case when the timers array was empty.
            $this->timers[] = [$triggerTime, $task];

            return;
        }

        // We need to insert these values in the timers array, but the timers
        // array must be in reverse-order of trigger times.
        //
        // So here we search the array for the insertion point.
        $index = \count($this->timers) - 1;
        while (true) {
            if ($triggerTime < $this->timers[$index][0]) {
                \array_splice(
                    $this->timers,
                    $index + 1,
                    0,
                    [[$triggerTime, $task]]
                );
                break;
            } elseif (0 === $index) {
                \array_unshift($this->timers, [$triggerTime, $task]);
                break;
            }
            --$index;
        }
    }

    public static function value($value)
    {
        return new ReturnValueCoroutine($value);
    }

    public static function plain($value)
    {
        return new PlainValueCoroutine($value);
    }

    /**
     * Wait on keyboard input.
     * Will not block other task on `Linux`, will continue other tasks until `enter` key is pressed,
     * Will block on Windows, once an key is typed/pressed, will continue other tasks `ONLY` if no key is pressed.
     * - This function needs to be prefixed with `yield`
     *
     * @return string
     */
    public static function input(int $size = 256, bool $error = false)
    {
        //Check on STDIN stream
        $blocking = \stream_set_blocking(\STDIN, false);
        if ($error && !$blocking) {
            throw new InvalidArgumentException('Non-blocking STDIN, could not be enabled.');
        }

        // @codeCoverageIgnoreStart
        yield Kernel::readWait(\STDIN);
        if (\IS_WINDOWS) {
            $windows7 = \strpos(\php_uname('v'), 'Windows 7') !== false;
            // kinda of workaround to allow non blocking under Windows 10, if no key is typed, will block after key press
            if (!$blocking) {
                while (true) {
                    $tell = \ftell(\STDIN);
                    if (\is_int($tell) || $windows7)
                        break;
                    else
                        yield;
                }
            }
        }

        return \stream_get_line(\STDIN, $size, \EOL);
        // @codeCoverageIgnoreEnd
    }

    public static function create(\Generator $gen)
    {
        $stack = new \SplStack;
        $exception = null;

        for (;;) {
            try {
                if ($exception instanceof \Throwable) {
                    $gen->throw($exception);
                    $exception = null;
                    continue;
                }

                $value = $gen->current();
                if ($value instanceof \Generator) {
                    $stack->push($gen);
                    $gen = $value;
                    continue;
                }

                $isReturnValue = $value instanceof ReturnValueCoroutine;
                if (!$gen->valid() || $isReturnValue) {
                    if ($stack->isEmpty()) {
                        return;
                    }

                    $return = null;
                    if (!$gen->valid() && !$isReturnValue) {
                        $return = $gen->getReturn();
                    }

                    $gen = $stack->pop();
                    $gen->send($isReturnValue ? $value->getValue() : $return);
                    continue;
                }

                if ($value instanceof PlainValueCoroutine) {
                    $value = $value->getValue();
                }

                try {
                    $sendValue = (yield $gen->key() => $value);
                } catch (\Throwable $e) {
                    $gen->throw($e);
                    continue;
                }

                $gen->send($sendValue);
            } catch (\Throwable $e) {
                if ($stack->isEmpty()) {
                    throw $e;
                }

                $gen = $stack->pop();
                $exception = $e;
            }
        }
    }
}
