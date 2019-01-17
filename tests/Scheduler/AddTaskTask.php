<?php

namespace Async\Tests\Scheduler;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;
use Async\Coroutine\Tasks\TaskInterface;

class AddTaskTask extends AbstractTask 
{
    protected $childTask = null;
    
    public function __construct(TaskInterface $task) 
	{
        $this->childTask = $task;
    }
    
    protected function doTick(SchedulerInterface $scheduler) 
	{
        // Just schedule the task that we were told to schedule
        $scheduler->schedule($this->childTask);
        
        // Once we have done our work of scheduling the child task, we are successful
        $this->successful = true;
    }
}
