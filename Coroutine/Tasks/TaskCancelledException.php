<?php

namespace Async\Coroutine\Tasks;

use Exception;
use Async\Coroutine\Tasks\TaskInterface;

/**
 * Exception thrown when a task is cancelled
 */
class TaskCancelledException extends Exception 
{
    /**
     * The cancelled task
     *
     * @var Task
     */
    protected $task = null;
    
    public function __construct(TaskInterface $task) 
	{
        $this->task = $task;
        
        parent::__construct("Task cancelled");
    }
    
    /**
     * Gets the cancelled task
     * 
     * @return Task
     */
    public function getCancelledTask() 
	{
        return $this->task;
    }
}
