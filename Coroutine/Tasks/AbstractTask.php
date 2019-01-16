<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;

abstract class AbstractTask implements TaskInterface 
{
    /**
     * Indicates if the task has completed successfully
     *
     * @var boolean
     */
    protected $successful = false;
    /**
     * The result associated with the successful completion (note that this can
     * be null)
     *
     * @var mixed
     */
    protected $result = null;
    
    /**
     * The exception that caused the task to fault
     *
     * @var \Exception
     */
    protected $exception = null;
    
    /**
     * Indicates if the task was cancelled
     *
     * @var boolean
     */
    protected $cancelled = false;
    
    /**
     * {@inheritdoc}
     */
    public function isComplete() {
        // A task is complete if it has either completed successfully, failed or
        // been cancelled
        return $this->isSuccessful() || $this->isFaulted() || $this->isCancelled();
    }
    
    /**
     * {@inheritdoc}
     */
    public function isSuccessful() {
        return $this->successful;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isFaulted() {
        // A task is faulted if it has an exception set
        return ($this->exception !== null);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isCancelled() {
        return $this->cancelled;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getResult() {
        return $this->result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getException() {
        return $this->exception;
    }
    
    /**
     * {@inheritdoc}
     */
    public function tick(SchedulerInterface $scheduler) {
        // If the task is complete, there is nothing to do
        if( $this->isComplete() ) return;
        
        // Call the method that performs actual work
        $this->doTick($scheduler);
    }
    
    /**
     * Method that performs the actual work
     */
    protected abstract function doTick(SchedulerInterface $scheduler);
    
    /**
     * {@inheritdoc}
     */
    public function cancel() {
        // You can't cancel a completed task
        if( $this->isComplete() ) return;
        
        $this->cancelled = true;
    }
}
