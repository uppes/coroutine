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

    protected $readStreams = [];    
    protected $readCallbacks = [];

    protected $writeStreams = [];
    protected $writeCallbacks = [];	

    // resourceID => [socket, tasks]
    protected $waitingForRead = [];
    protected $waitingForWrite = [];

    public function __construct()
	{
        $this->taskQueue = new \SplQueue();
    }

    public function add(\Generator $coroutine) 
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

    public function remove(int $tid) 
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
	
    public function isEmpty() 
	{
        return $this->taskQueue->isEmpty() 
			&& empty($this->waitingForRead) 
			&& empty($this->waitingForWrite);
    }
	
    public function run() 
	{
        $this->add($this->ioSocketPoll());
        return $this->doRun();
    }

    protected function doRun() 
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

    protected function runCoroutines() 
	{
        return $this->doRun();
    }

    protected function ioPoll($timeout) 
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

        if (!stream_select($rSocks, $wSocks, $eSocks, $timeout)) {
            return;
        }

        foreach ($rSocks as $socket) {
            list(, $tasks) = $this->waitingForRead[(int) $socket];
            unset($this->waitingForRead[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }

        foreach ($wSocks as $socket) {
            list(, $tasks) = $this->waitingForWrite[(int) $socket];
            unset($this->waitingForWrite[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }
    }

    protected function ioSocketPoll() 
	{
        while (true) {
            if ($this->taskQueue->isEmpty()) {
                $this->ioPoll(0);
                break;
            } else {
                $this->ioPoll(0);
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
        if ($task instanceof TaskInterface) {
            if (isset($this->waitingForRead[(int) $socket])) {
                $this->waitingForRead[(int) $socket][1][] = $task;
            } else {
                $this->waitingForRead[(int) $socket] = [$socket, [$task]];
            }
        } elseif (is_callable($task)) {
            $this->readStreams[(int) $stream] = $stream;
            $this->readCallbacks[(int) $stream] = $task;
        } else {
            throw new \RunTimeException('Invalid/missing parameters!');
        }
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
        if ($task instanceof TaskInterface) {
            if (isset($this->waitingForWrite[(int) $socket])) {
                $this->waitingForWrite[(int) $socket][1][] = $task;
            } else {
                $this->waitingForWrite[(int) $socket] = [$socket, [$task]];
            }
        } elseif (is_callable($task)) {
            $this->writeStreams[(int) $stream] = $stream;
            $this->writeCallbacks[(int) $stream] = $task; 
        } else {
            throw new \RunTimeException('Invalid/missing parameters!');
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
