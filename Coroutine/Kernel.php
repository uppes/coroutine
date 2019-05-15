<?php

namespace Async\Coroutine;

use Async\Coroutine\Channel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\TaskInterface;

/**
 * The Kernel
 * This class is used for Communication between the tasks and the scheduler
 * 
 * The `yield` keyword in your code, act both as an interrupt and as a way to 
 * pass information to (and from) the scheduler.
 */
class Kernel 
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
		return new Kernel(
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
	public static function createTask(\Generator $coroutines) 
	{
		return new Kernel(
			function(TaskInterface $task, Coroutine $coroutine) use ($coroutines) {
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
			function(TaskInterface $task, Coroutine $coroutine) {
				$task->sendValue(Channel::make($task, $coroutine));
				$coroutine->schedule($task);
			}
		);
	}

	/**
	 * Set Channel by task id, similar to Google Go language
	 * 
     * @param Channel $channel
	 */
	public static function receiver(Channel $channel)
	{
		return new Kernel(
			function(TaskInterface $task, Coroutine $coroutine) use ($channel) {
				$channel->receiver($task->taskId());
				$coroutine->schedule($task);
			}
		);
	}

	/**
	 * Set Channel by task id, similar to Google Go language
	 * 
     * @param mixed $message
	 * @param int $taskId
	 */
	public static function receive(Channel $channel)
	{
		return new Kernel(
			function(TaskInterface $task, Coroutine $coroutine) use ($channel) {
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
			function(TaskInterface $task, Coroutine $coroutine) use ($channel, $message, $taskId) {
				$taskList = $coroutine->taskList();				    
		
				if (isset($taskList[$channel->receiverId()]))
					$newTask = $taskList[$channel->receiverId()];
				elseif (isset($taskList[$taskId]))
					$newTask = $taskList[$taskId];
				else
					$newTask = $channel->senderTask();

				$newTask->sendValue($message);
				$coroutine->schedule($newTask);
			}
		);
	}

	public static function async($callable, ...$args) 
	{
	}

	public static function await($callable, ...$args) 
	{
		return new Kernel(
			function(TaskInterface $task, Coroutine $coroutine) use ($callable, $args) {
				$task->sendValue($coroutine->createTask(\awaitAble($callable, ...$args)));				
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
	public static function cancelTask($tid) 
	{
		return new Kernel(
			function(TaskInterface $task, Coroutine $coroutine) use ($tid) {
				if ($coroutine->cancelTask($tid)) {					
					$task->sendValue(true);
					$task->setState('terminated');
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
	public static function readWait($socket) 
	{
		return new Kernel(
			function(TaskInterface $task, Coroutine $coroutine) use ($socket) {
				$coroutine->addReader($socket, $task);
			}
		);
	}

    /**
     * Wait on write stream socket to be ready to be written to.
     * 
     * @param resource $socket
     */
	public static function writeWait($socket) 
	{
		return new Kernel(
			function(TaskInterface $task, Coroutine $coroutine) use ($socket) {
				$coroutine->addWriter($socket, $task);
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
			function(TaskInterface $task, Coroutine $coroutine) use ($delay, $result) {
				$coroutine->addTimeout(function () use ($task, $coroutine, $result) {
					if (!empty($result)) 
						$task->sendValue($result);
					$coroutine->schedule($task);
				}, $delay);
			}
		);
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
			function(TaskInterface $task, Coroutine $coroutine) use ($callable, $timeout) {
				if ($callable instanceof \Generator) {
					$taskId = $coroutine->createTask($callable);
				} else {
					$taskId = $coroutine->createTask(\awaitAble($callable));					
				}
				
				$coroutine->addTimeout(function () use ($taskId, $timeout, $task, $coroutine) {
					if (!empty($timeout)) {
						$coroutine->cancelTask($taskId);
						$task->exception(new \RuntimeException('The operation has exceeded the given deadline'));
						$coroutine->schedule($task);
					} else
						$coroutine->schedule($task);
						
				}, $timeout);
			}
		);
	}
}
