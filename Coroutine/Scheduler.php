<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Coroutine;

use Async\Coroutine\Task;
use Async\Coroutine\Syscall;
use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;

class Scheduler implements SchedulerInterface
{
    /**
     * Indicates whether the scheduler is currently running
     *
     * @var boolean
     */
    protected $running = false;
	
    /**
     * Indicates whether it's a coroutine, otherwise use \SplObjectStorage queuing
     *
     * @var boolean
     */
    protected $isCoroutine = false;
	
    /**
     * The queue of tasks to execute next time
     *
     * @var \SplObjectStorage
     */
    protected $next = null;

    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $taskQueue;

    // resourceID => [socket, tasks]
    protected $waitingForRead = [];
    protected $waitingForWrite = [];

    public function __construct() 
	{
        $this->taskQueue = new \SplQueue();
        $this->next = new \SplObjectStorage();
    }

    public function coroutine(\Generator $coroutine) 
	{
		$this->isCoroutine = true;
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    public function schedule(TaskInterface $task, $delay = null, $tickInterval = null) 
	{
        // We don't support the use of timers with this scheduler
        if( null !== $delay || null !== $tickInterval ) {
            throw new \RuntimeException("Timers are not supported by this scheduler implementation");
        }
		
		if ($this->isCoroutine)
			$this->taskQueue->enqueue($task);
		else
			// Just add the task to be run next tick
			$this->next->attach($task);
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
		if ($this->isCoroutine) {			
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
		} else {
			$this->doActionTick();
		}
    }

    public function runCoroutines() 
	{
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

    /**
     * {@inheritdoc}
     */
    protected function actionTick() {
        // Get the queue of actions for this tick
        // This is in case any of the actions adds an action to be called on
        // the next tick
        $tasks = $this->next;
        
        // Initialise the queue for next tick
        $this->next = new \SplObjectStorage();
        
        foreach( $tasks as $task ) {            
            // Execute the task
            $task->tick($this);
            // If the task is not complete, reschedule it for the next tick
            if( !$task->isComplete() ) $this->schedule($task);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doActionTick() {
        $this->running = true;
        
        // Tick until there are no more tasks or we are manually stopped
        do {
            $this->actionTick();
        } while( $this->running && count($this->next) > 0 );
    }

    /**
     * {@inheritdoc}
     */
    public function stop() {
        $this->running = false;
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
