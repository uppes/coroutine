<?php

namespace Async\Coroutine;

use Async\Coroutine\Call;
use Async\Coroutine\Task;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\AbstractCoroutine;
use Async\Coroutine\ReturnValueCoroutine;
use Async\Coroutine\PlainValueCoroutine;
use Async\Coroutine\CoroutineInterface;

class Coroutine implements CoroutineInterface
{	
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $taskQueue;

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

    public function __construct()
	{
        $this->taskQueue = new \SplQueue();
    }

    public function addTask(\Generator $coroutine) 
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

    public function removeTask(int $tid) 
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
	
    public function hasCoroutines() 
	{
        return ! $this->taskQueue->isEmpty()
            && ! empty($this->readStreams) 
            && ! empty($this->writeStreams);
    }
	
    public function run() 
	{
        $this->addTask($this->ioSocketPoll());
        return $this->runCoroutines();
    }

    protected function runCoroutines() 
	{
		while (!$this->taskQueue->isEmpty()) {
			$task = $this->taskQueue->dequeue();
			$value = $task->run();

			if ($value instanceof Call) {
				try {
					$value($task, $this);
				} catch (\Exception $e) {
					$task->exception($e);
					$this->schedule($task);
				}
				continue;
			}

			if ($task->isFinished()) {
				unset($this->taskMap[$task->taskId()]);
			} else {
				$this->schedule($task);
			}
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
                    $this->removeReadStream($readStream);
                    $this->schedule($readCb);
                }

                foreach ($write as $writeStream) {
                    $writeCb = $this->writeCallbacks[(int) $writeStream];
                    $this->removeWriteStream($writeStream);
                    $this->schedule($writeCb);
                }
            }
        } else {
            return;
        }
    }

    protected function ioSocketPoll() 
	{
        while (true) {
            if (! $this->hasCoroutines()) {
                break;
            } else {
                $streamWait = null;
                if (! $this->taskQueue->isEmpty()) {
                    $streamWait = 0 ;
                }

                $this->runStreams($streamWait);
            }
            yield;
        }
    }

    /**
     * Adds a read stream.
     */
    public function addReadStream($stream, $task)
    {
        $this->waitForRead($stream, $task);
    }

    public function waitForRead($socket, $task) 
	{
        $this->readStreams[(int) $socket] = $socket;
        $this->readCallbacks[(int) $socket] = $task;
    }

    /**
     * Adds a write stream.
     */
    public function addWriteStream($stream, $task)
    {
        $this->waitForWrite($stream, $task);
    }

    public function waitForWrite($socket, $task) 
	{
        $this->writeStreams[(int) $socket] = $socket;
        $this->writeCallbacks[(int) $socket] = $task; 
    }

    /**
     * Stop watching a stream for reads.
     */
    public function removeReadStream($stream)
    {
        unset(
            $this->readStreams[(int) $stream],
            $this->readCallbacks[(int) $stream]
        );
    }

    /**
     * Stop watching a stream for writes.
     */
    public function removeWriteStream($stream)
    {
        unset(
            $this->writeStreams[(int) $stream],
            $this->writeCallbacks[(int) $stream]
        );
    }

	public static function value($value) 
	{
		return new ReturnValueCoroutine($value);
	}

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
