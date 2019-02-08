<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Coroutine;

use Async\Coroutine\Task;
use Async\Coroutine\Scheduler;

class Call 
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

	public static function taskId() 
	{
		return new Call(
			function(Task $task, Scheduler $scheduler) {
				$task->sendValue($task->taskId());
				$scheduler->schedule($task);
			}
		);
	}

	public static function coroutine(\Generator $coroutine) 
	{
		return new Call(
			function(Task $task, Scheduler $scheduler) use ($coroutine) {
				$task->sendValue($scheduler->coroutine($coroutine));
				$scheduler->schedule($task);
			}
		);
	}

	public static function killTask($tid) 
	{
		return new Call(
			function(Task $task, Scheduler $scheduler) use ($tid) {
				if ($scheduler->killTask($tid)) {
					$scheduler->schedule($task);
				} else {
					throw new \InvalidArgumentException('Invalid task ID!');
				}
			}
		);
	}

	public static function waitForRead($socket) 
	{
		return new Call(
			function(Task $task, Scheduler $scheduler) use ($socket) {
				$scheduler->waitForRead($socket, $task);
			}
		);
	}

	public static function waitForWrite($socket) 
	{
		return new Call(
			function(Task $task, Scheduler $scheduler) use ($socket) {
				$scheduler->waitForWrite($socket, $task);
			}
		);
	}
}
