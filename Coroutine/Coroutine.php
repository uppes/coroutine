<?php

declare(strict_types = 1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Task;
use Async\Coroutine\Parallel;
use Async\Coroutine\Process;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\ReturnValueCoroutine;
use Async\Coroutine\PlainValueCoroutine;
use Async\Coroutine\CoroutineInterface;
use Async\Processor\ProcessInterface;

/**
 * The Scheduler
 *
 * @see https://docs.python.org/3/library/asyncio-task.html#coroutines
 */
class Coroutine implements CoroutineInterface
{
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $completedMap = [];
    protected $taskQueue;

    /**
     * A list of timers, added by addTimeout.
     *
     * @var array
     */
    protected $timers = [];

    /**
     * Combined list of readable socket/streams and read callbacks.
     *
     * for stream_select, indexed by stream id.
     * @var resource[] [stream, tasks]
     */
    protected $waitingForRead = [];

    /**
     * Combined list of writable socket/streams and write callbacks.
     *
     * for stream_select, indexed by stream id.
     * @var resource[] [stream, tasks]
     */
    protected $waitingForWrite = [];

    protected $pcntl = null;
    protected $process = null;
    protected $parallel = null;

    public function __construct()
	{
        global $__coroutine__;
        $__coroutine__ = $this;

        $this->parallel = new Parallel($this);
        $this->taskQueue = new \SplQueue();
    }

    public function parallelInstance(): Parallel
    {
        return $this->parallel;
    }

    public function processInstance($timedOutCallback = null, $finishCallback = null, $failCallback = null)
    {
        if (!empty($this->process)) {
            $this->process->stopAll();
            $this->process = null;
        }

        $this->process = new Process($this, $timedOutCallback, $finishCallback, $failCallback);
        return $this->process;
    }

    /**
     * Add callable for parallel processing, in an separate php process
	 *
     * @see https://docs.python.org/3.8/library/asyncio-subprocess.html#creating-subprocesses
     *
     * @param callable $callable
     * @param int $timeout
     *
     * @return ProcessInterface
     */
	public function createSubProcess($callable, int $timeout = 300): ProcessInterface
    {
		return $this->parallel->add($callable, $timeout);
    }

    public function isPcntl(): bool
    {
        $this->pcntl = \extension_loaded('pcntl') && \function_exists('pcntl_async_signals')
        && \function_exists('posix_kill');

        return $this->pcntl;
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

    public function shutdown()
	{
        if (!empty($this->taskMap)) {
            $map = \array_reverse($this->taskMap, true);
            $keys = \array_keys($map);
            foreach($keys as $id) {
                if ($id == 1)
                    break;
                $this->cancelTask((int) $id);
            }
        }
    }

    public function cancelTask(int $tid)
	{
        if (!isset($this->taskMap[$tid])) {
            return false;
        }

        unset($this->taskMap[$tid]);

        foreach ($this->taskQueue as $i => $task) {
            if ($task->taskId() === $tid) {
                $task->clearResult();
                $task->customData();
                $task->customState();
                $task->setState('cancelled');
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }

    public function taskList()
	{
        if (!isset($this->taskMap)) {
            return null;
        }

        return $this->taskMap;
    }

    public function completedList()
	{
        if (!isset($this->completedMap)) {
            return null;
        }

        return $this->completedMap;
    }

    public function updateCompleted($taskMap = [])
	{
        $this->completedMap = $taskMap;
    }

    public function run()
	{
        $this->createTask($this->ioWaiting());
		return $this->runCoroutines();
    }

    public function runCoroutines()
	{
		while (!$this->taskQueue->isEmpty()) {
			$task = $this->taskQueue->dequeue();
            $task->setState('running');
            $task->cyclesAdd();
			$value = $task->run();

			if ($value instanceof Kernel) {
				try {
					$value($task, $this);
				} catch (\Async\Coroutine\Exceptions\CancelledError $e) {
                    $task->clearResult();
                    $task->setState('cancelled');
					$task->setException($e);
					$this->schedule($task);
				} catch (\Exception $e) {
                    $task->clearResult();
                    $task->setState('erred');
					$task->setException($e);
					$this->schedule($task);
                }
				continue;
			}

			if ($task->isFinished()) {
                $task->setState('completed');
                $id = $task->taskId();
                $this->completedMap[$id] = $task;
				unset($this->taskMap[$id]);
			} else {
                $task->setState('rescheduled');
				$this->schedule($task);
            }
		}
    }

    /**
     * Runs all pending timers.
     *
     * @return int|void
     */
    protected function runTimers()
    {
        $now = \microtime(true);
        while (($timer = \array_pop($this->timers)) && $timer[0] < $now) {
            if ($timer[1] instanceof TaskInterface) {
                $this->schedule($timer[1]);
            }  elseif ($timer[1]() instanceof \Generator) {
                $this->createTask($timer[1]());
            }
        }

        // Add the last timer back to the array.
        if ($timer) {
            $this->timers[] = $timer;

            return \max(0, $timer[0] - \microtime(true));
        }
    }

    protected function ioWaiting()
	{
        while (true) {
            if ($this->taskQueue->isEmpty()
                && empty($this->waitingForRead)
                && empty($this->waitingForWrite)
                && empty($this->timers)
                && $this->process->isEmpty()
            ) {
                break;
            } else {
                $streamWait = null;
                $this->process->processing();

                $nextTimeout = $this->runTimers();
                if (\is_numeric($nextTimeout))
                    // Wait until the next Timeout should trigger.
                    $streamWait = $nextTimeout * 1000000;
                elseif (! $this->taskQueue->isEmpty())
                    // There's a pending 'createTask'. Don't wait.
                    $streamWait = 0;
                elseif (! $this->process->isEmpty())
                    // There's a running 'process', wait some before rechecking.
                    $streamWait = $this->process->sleepingTime();

                $this->ioSocketStream($streamWait);
            }
            yield;
        }
    }

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
            $timeout ? (int) ($timeout * (($timeout === null) ? 1000000 : 1)) : 0)
        ) {
            if (!empty(\error_get_last()['message'])) {
                \panic(\error_get_last()['message']);
            }

            return;
        }

        foreach ($rSocks as $socket) {
            list(, $tasks) = $this->waitingForRead[(int) $socket];
            $this->removeReader($socket);

            foreach ($tasks as $task) {
                if ($task instanceof TaskInterface) {
                    $this->schedule($task);
                } elseif ($task() instanceof \Generator) {
                    $this->createTask($task());
                }
            }
        }

        foreach ($wSocks as $socket) {
            list(, $tasks) = $this->waitingForWrite[(int) $socket];
            $this->removeWriter($socket);

            foreach ($tasks as $task) {
                if ($task instanceof TaskInterface) {
                    $this->schedule($task);
                } elseif ($task() instanceof \Generator) {
                    $this->createTask($task());
                }
            }
        }
    }

    public function addReader($stream, $task)
    {
        if (isset($this->waitingForRead[(int) $stream])) {
            $this->waitingForRead[(int) $stream][1][] = $task;
        } else {
            $this->waitingForRead[(int) $stream] = [$stream, [$task]];
        }
    }

    public function addWriter($stream, $task)
    {
        if (isset($this->waitingForWrite[(int) $stream])) {
            $this->waitingForWrite[(int) $stream][1][] = $task;
        } else {
            $this->waitingForWrite[(int) $stream] = [$stream, [$task]];
        }
    }

    public function removeReader($stream)
    {
        unset($this->waitingForRead[(int) $stream]);
    }

    public function removeWriter($stream)
    {
        unset($this->waitingForWrite[(int) $stream]);
    }

    /**
     * Executes a function after x seconds.
     *
     * @param callable $task
     * @param float $timeout
     */
    public function addTimeout($task, float $timeout)
    {
        $triggerTime = \microtime(true) + ($timeout);

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

    /**
     * Executes a function every x seconds.
     *
     * @param callable $task
     * @param float $timeout
     */
    public function setInterval($task, float $timeout): array
    {
        $keepGoing = true;
        $f = null;

        $f = function () use ($task, &$f, $timeout, &$keepGoing) {
            if ($keepGoing) {
                $task();
                $this->addTimeout($f, $timeout);
            }
        };
        $this->addTimeout($f, $timeout);

        return ['I\'m an implementation detail', &$keepGoing];
    }

    /**
     * Stops a running interval.
     */
    public function clearInterval(array $intervalId)
    {
        $intervalId[1] = false;
    }

	public static function value($value)
	{
		return new ReturnValueCoroutine($value);
    }

    /**
     * Creates an object instance of the value which will signal
     * `Coroutine::create` that it’s a return value.
     *
     * @param mixed $value
     * @return PlainValueCoroutine
     */
	public static function plain($value)
	{
		return new PlainValueCoroutine($value);
    }

	public static function create(\Generator $gen)
	{
		$stack = new \SplStack;
		$exception = null;

		for (;;) {
			try {
				if ($exception) {
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
				} catch (\Exception $e) {
					$gen->throw($e);
					continue;
				}

				$gen->send($sendValue);
			} catch (\Exception $e) {
				if ($stack->isEmpty()) {
					throw $e;
				}

				$gen = $stack->pop();
				$exception = $e;
			}
		}
	}
}
