<?php

namespace Async\Coroutine;

use Async\Coroutine\Coroutine;
use Async\Coroutine\TaskInterface;

/**
 * This class is used for Communication between the tasks and the scheduler
 * 
 * The yield also act both as an interrupt and as a way to 
 * pass information to (and from) the scheduler.
 */
class Call 
{
    protected $callback;

    public function __construct(callable $callback) 
	{
        $this->callback = $callback;
    }

	/**
	 * Tells the scheduler to pass the calling task and itself into the function.
	 * 
	 * @param TaskInterface $task
	 * @param Coroutine $coroutine
	 * @return mixed
	 */
    public function __invoke(TaskInterface $task, Coroutine $coroutine) 
	{
        $callback = $this->callback;
        return $callback($task, $coroutine);
    }

	/**
	 * Return the task ID
	 * 
	 * @return int
	 */
	public static function taskId() 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) {
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
	public static function addTask(\Generator $coroutines) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($coroutines) {
				$task->sendValue($coroutine->addTask($coroutines));
				$coroutine->schedule($task);
			}
		);
	}

	/**
	 * kill/remove an task using task id
	 * 
	 * @param int $tid
	 * @throws \InvalidArgumentException
	 */
	public static function removeTask($tid) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($tid) {
				if ($coroutine->removeTask($tid)) {
					$coroutine->schedule($task);
				} else {
					throw new \InvalidArgumentException('Invalid task ID!');
				}
			}
		);
	}

    /**
     * Wait on read stream socket to be ready read from.
     * 
     * @param resource $socket
     */
	public static function waitForRead($socket) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($socket) {
				$coroutine->addReadStream($socket, $task);
			}
		);
	}

    /**
     * Wait on write stream socket to be ready to be written to.
     * 
     * @param resource $socket
     */
	public static function waitForWrite($socket) 
	{
		return new Call(
			function(TaskInterface $task, Coroutine $coroutine) use ($socket) {
				$coroutine->addWriteStream($socket, $task);
			}
		);
	}
}
