<?php

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Task;
use Async\Coroutine\Spawn;
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
    protected $taskQueue;

    /**
     * A list of timers, added by addTimeout.
     *
     * @var array
     */
    protected $timers = [];

    /**
     * List of readable streams for stream_select, indexed by stream id.
     *
     * @var resource[]
     */
    protected $readStreams = [];

    /**
     * List of writable streams for stream_select, indexed by stream id.
     *
     * @var resource[]
     */
    protected $writeStreams = [];

    /**
     * List of read callbacks, indexed by stream id.
     *
     * @var callback[]
     */
    protected $readCallbacks = [];

    /**
     * List of write callbacks, indexed by stream id.
     *
     * @var callback[]
     */
    protected $writeCallbacks = [];	

    // resourceID => [socket, tasks]
    protected $waitingForRead = [];
    protected $waitingForWrite = [];

    protected $pcntl = null;
    protected $process = null;
    protected $spawn = null;

    public function __construct()
	{
        global $__coroutine__;
        $__coroutine__ = $this;
        
        $this->spawn = new Spawn($this);
        $this->taskQueue = new \SplQueue();
    }

    public function spawnInstance(): Spawn
    {
        return $this->spawn;
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
     * @param callable $callable
     * @param int $timeout 
     *
     * @return ProcessInterface
     */
	public function createProcess($callable, int $timeout = 300): ProcessInterface
    {		
		return $this->spawn->add($callable, $timeout);
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

    public function cancelTask(int $tid) 
	{
        if (!isset($this->taskMap[$tid])) {
            return false;
        }
    
        unset($this->taskMap[$tid]);
    
        foreach ($this->taskQueue as $i => $task) {
            if ($task->taskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }
    
        return true;
    }
		
    public function gather(...$taskId) 
	{
        // @todo
    }

    public function run() 
	{
        $this->createTask($this->ioWaiting());
		return $this->runCoroutines();
    }

    protected function runCoroutines() 
	{
		while (!$this->taskQueue->isEmpty()) {
			$task = $this->taskQueue->dequeue();
			$value = $task->run();

			if ($value instanceof Kernel) {
				try {
					$value($task, $this);
				} catch (\Exception $e) {
					$task->exception($e);
					$this->schedule($task);
				}
				continue;
			}

			if ($task->isFinished()) {
                $task->setState('completed');
				unset($this->taskMap[$task->taskId()]);
			} else {
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
            } else {
                $timer[1]();
            }
        }

        // Add the last timer back to the array.
        if ($timer) {
            $this->timers[] = $timer;

            return \max(0, $timer[0] - \microtime(true));
        }
    }

    protected function runStreams($timeout)
    {
        if ($this->readStreams || $this->writeStreams) {
            $read = $this->readStreams;
            $write = $this->writeStreams;
            $except = null;
            if (\stream_select(
                $read, 
                $write, 
                $except, 
                (null === $timeout) ? null : 0, 
                $timeout ? (int) ( $timeout * (($timeout === null) ? 1000000 : 1)) : 0)
            ) {
                foreach ($read as $readStream) {
                    $readCb = $this->readCallbacks[(int) $readStream];
                    if ($readCb instanceof TaskInterface) {
                        $this->removeReadStream($readStream);
                        $this->schedule($readCb);
                    } else {
                        $readCb();
                    }
                }

                foreach ($write as $writeStream) {
                    $writeCb = $this->writeCallbacks[(int) $writeStream];
                    if ($writeCb instanceof TaskInterface) {
                        $this->removeWriteStream($writeStream);
                        $this->schedule($writeCb);
                    } else {
                        $writeCb();
                    }
                }
            }
        }
    }

    protected function ioWaiting() 
	{
        while (true) {
            if ($this->taskQueue->isEmpty()
                && empty($this->readStreams) 
                && empty($this->writeStreams)
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

                $this->runStreams($streamWait);
            }
            yield;
        }
    }

    public function addReadStream($stream, $task)
    {
        $this->readStreams[(int) $stream] = $stream;
        $this->readCallbacks[(int) $stream] = $task;
    }

    public function addWriteStream($stream, $task)
    {
        $this->writeStreams[(int) $stream] = $stream;
        $this->writeCallbacks[(int) $stream] = $task; 
    }

    public function removeReadStream($stream)
    {
        unset(
            $this->readStreams[(int) $stream],
            $this->readCallbacks[(int) $stream]
        );
    }

    public function removeWriteStream($stream)
    {
        unset(
            $this->writeStreams[(int) $stream],
            $this->writeCallbacks[(int) $stream]
        );
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

					$gen = $stack->pop();
					$gen->send($isReturnValue ? $value->getValue() : NULL);
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
