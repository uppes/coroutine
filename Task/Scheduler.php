<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Task;

use Async\Task\Task;
use Async\Task\Syscall;

class Scheduler
{
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $taskQueue;

    // resourceID => [socket, tasks]
    protected $waitingForRead = [];
    protected $waitingForWrite = [];

    public function __construct() 
	{
        $this->taskQueue = new \SplQueue();
    }

    public function coroutine(\Generator $coroutine) 
	{
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    public function schedule(Task $task) 
	{
        $this->taskQueue->enqueue($task);
    }

    public function killTask($tid) 
	{
        if (!isset($this->taskMap[$tid])) {
            return false;
        }
    
        unset($this->taskMap[$tid]);
    
        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }
    
        return true;
    }
	
    public function run() 
	{
        $this->coroutine($this->ioPollTask());

        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $retval = $task->run();

            if ($retval instanceof Syscall) {
                try {
                    $retval($task, $this);
                } catch (\Exception $e) {
                    $task->setException($e);
                    $this->schedule($task);
                }
                continue;
            }

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
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

    protected function ioPollTask() 
	{
        while (true) {
            if ($this->taskQueue->isEmpty()) {
                $this->ioPoll(null);
            } else {
                $this->ioPoll(0);
            }
            yield;
        }
    }

    public function waitForRead($socket, Task $task) 
	{
        if (isset($this->waitingForRead[(int) $socket])) {
            $this->waitingForRead[(int) $socket][1][] = $task;
        } else {
            $this->waitingForRead[(int) $socket] = [$socket, [$task]];
        }
    }

    public function waitForWrite($socket, Task $task) 
	{
        if (isset($this->waitingForWrite[(int) $socket])) {
            $this->waitingForWrite[(int) $socket][1][] = $task;
        } else {
            $this->waitingForWrite[(int) $socket] = [$socket, [$task]];
        }
    }
}
