<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Coroutine;

use Async\Coroutine\Task;
use Async\Coroutine\Scheduler;

class Syscall 
{
    protected $callback;

    public function __construct(callable $callback) 
	{
        $this->callback = $callback;
    }

    public function __invoke(Task $task, Scheduler $scheduler) 
	{
        $callback = $this->callback; // Yes, PHP sucks
        return $callback($task, $scheduler);
    }

	public function getTaskId() 
	{
		return new Syscall(
			function(Task $task, Scheduler $scheduler) {
				$task->setSendValue($task->getTaskId());
				$scheduler->schedule($task);
			}
		);
	}

	public function newTask(\Generator $coroutine) 
	{
		return new Syscall(
			function(Task $task, Scheduler $scheduler) use ($coroutine) {
				$task->setSendValue($scheduler->coroutine($coroutine));
				$scheduler->schedule($task);
			}
		);
	}

	public function killTask($tid) 
	{
		return new Syscall(
			function(Task $task, Scheduler $scheduler) use ($tid) {
				if ($scheduler->killTask($tid)) {
					$scheduler->schedule($task);
				} else {
					throw new \InvalidArgumentException('Invalid task ID!');
				}
			}
		);
	}

	public function waitForRead($socket) 
	{
		return new Syscall(
			function(Task $task, Scheduler $scheduler) use ($socket) {
				$scheduler->waitForRead($socket, $task);
			}
		);
	}

	public function waitForWrite($socket) 
	{
		return new Syscall(
			function(Task $task, Scheduler $scheduler) use ($socket) {
				$scheduler->waitForWrite($socket, $task);
			}
		);
	}
}
