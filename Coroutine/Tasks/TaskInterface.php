<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\Cancelable;
use Async\Coroutine\SchedulerInterface;

interface TaskInterface extends Cancelable 
{
    /**
     * Indicates if the task has completed (either by running successfully,
     * failure or cancellation)
     * 
     * @return boolean
     */
    public function isComplete();
    
    /**
     * Indicates if the task completed successful
     * 
     * @return boolean
     */
    public function isSuccessful();
    
    /**
     * Indicates if there was an error running the task
     * 
     * @return boolean
     */
    public function isFaulted();
    
    /**
     * Gets the result of running the task, if there is one, or null otherwise
     * 
     * If the task is not complete, null is returned
     * 
     * @return mixed
     */
    public function getResult();
    
    /**
     * Gets the exception that caused the error, if there is one, or null otherwise
     * 
     * If the task is not complete, null is returned
     * 
     * @return \Exception
     */
    public function getException();
    
    /**
     * Perform one 'tick' of the task
     * 
     * @param Scheduler $scheduler
     */
    public function tick(SchedulerInterface $scheduler);
}
