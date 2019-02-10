<?php

namespace Async\Coroutine;

use Async\Coroutine\Coroutine;
use Async\Coroutine\TaskInterface;

class Call 
{
    protected $callback;

    public function __construct(callable $callback) 
	{
        $this->callback = $callback;
    }

    public function __invoke(TaskInterface $task, Coroutine $coroutine) 
	{
        $callback = $this->callback;
        return $callback($task, $coroutine);
    }

	public static function taskId() 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) {
				$task->sendValue($task->taskId());
				$coroutine->schedule($task);
			}
		);
	}

	public static function addTask(\Generator $coroutines) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($coroutines) {
				$task->sendValue($coroutine->add($coroutines));
				$coroutine->schedule($task);
			}
		);
	}

	public static function removeTask($tid) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($tid) {
				if ($coroutine->remove($tid)) {
					$coroutine->schedule($task);
				} else {
					throw new \InvalidArgumentException('Invalid task ID!');
				}
			}
		);
	}

	public static function waitForRead($socket) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($socket) {
				$coroutine->waitForRead($socket, $task);
			}
		);
	}

	public static function waitForWrite($socket) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($socket) {
				$coroutine->waitForWrite($socket, $task);
			}
		);
	}
}
