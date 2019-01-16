<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;
use Async\Coroutine\Tasks\TaskCancelledException;
use Async\Coroutine\Tasks\MultipleFailureException;

/**
 * Task that takes a set of tasks and waits for a specified number of them to
 * successfully complete before completing itself
 */
class SomeTask extends AbstractTask 
{
    /**
     * The tasks we are waiting for
     *
     * @var Task[]
     */
    protected $tasks = [];
    
    /**
     * The number of tasks we are waiting for
     *
     * @var type 
     */
    protected $howMany = 1;
    
    /**
     * Indicates if the child tasks have been scheduled yet
     *
     * @var boolean
     */
    protected $scheduled = false;
    
    /**
     * The results of the completed tasks
     *
     * @var array
     */
    protected $results = [];
    
    /**
     * The exceptions of the failed tasks
     *
     * @var array
     */
    protected $exceptions = [];
    
    /**
     * Create a new task that waits for $howMany of the given tasks to complete
     * before completing
     * 
     * The task result will be an array containing the results of the first
     * $howMany tasks to complete successfully, with keys preserved from the
     * $tasks array
     * 
     * If it becomes impossible for $howMany tasks to complete, the task will
     * be faulted, and the exception will be a {@see \Async\Task\MultipleFailureException}
     * consisting of the combined reasons for the failures
     * 
     * @param array $tasks
     */
    public function __construct(array $tasks, $howMany) 
	{
        $this->tasks = $tasks;
        $this->howMany = $howMany;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isSuccessful() 
	{
        // We are successful if $howMany tasks have returned successfully
        return count($this->results) >= $this->howMany;
    }

    /**
     * {@inheritdoc}
     */
    public function isFaulted() 
	{
        // We are faulted if it is not possible for $howMany tasks to succeed
        return count($this->exceptions) > (count($this->tasks) - $this->howMany);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult() 
	{
        return $this->results;
    }

    /**
     * {@inheritdoc}
     */
    public function getException() 
	{
        return $this->isFaulted() ? new MultipleFailureException($this->exceptions) : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function doTick(SchedulerInterface $scheduler) 
	{
        // Check for completed tasks
        foreach( $this->tasks as $key => $task ) {
            // If we are complete, we don't need to check any more tasks
            if( $this->isComplete() ) return;
            
            if( $task->isComplete() ) {
                if( $task->isFaulted() ) {
                    // If the task has failed, add the exception to our exceptions
                    // array
                    $this->exceptions[$key] = $task->getException();
                }
                else if( $task->isCancelled() ) {
                    // Cancelled tasks count as failures, so add a TaskCancelledException
                    // to our array
                    $this->exceptions[$key] = new TaskCancelledException($task);
                }
                else {
                    // Otherwise add the result of the task to our results array
                    $this->results[$key] = $task->getResult();
                }
            }
        }
        
        // If we have not yet scheduled our child tasks, schedule any incomplete ones
        if( !$this->scheduled ) {
            foreach( $this->tasks as $task ) {
                if( !$task->isComplete() ) {
                    $scheduler->schedule($task);
                }
            }
            
            $this->scheduled = true;
        }
    }    
}
