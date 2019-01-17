<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\Tasks\TaskInterface;

/**
 * Class to move logic for manipulating and inspecting the state of a generator
 * out of test cases
 */
class GeneratorHelper 
{
    // The value returned from the last yielded task
    protected $current = null;
    // The task to yield next time the generator is resumed
    protected $task = null;
    // The exception to throw next time the generator is resumed
    protected $exceptionToThrow = null;
    // The exception that was caught last time the generator was resumed
    protected $caughtException = null;
    
    public function generator() 
    {
        while( true ) {
            // If there is an exception to throw, throw it
            if( $this->exceptionToThrow ) 
                throw $this->exceptionToThrow;
            
            // If there is no task to yield, exit the loop
            if( $this->task === null ) 
                break;
            
            // Capture the task we will yield in a variable and null the instance
            // variable, so we don't automatically yield it again next
            $task = $this->task;
            $this->task = null;
            
            // Yield the task and receive the result back
            // Capture the result/any caught exceptions as required
            try {
                $this->current = ( yield $task );
                $this->caughtException = null;
            } catch( \Exception $exception ) {
                $this->caughtException = $exception;
                $this->current = null;
            }
        }
    }
    
    public function current() 
    {
        return $this->current;
    }
    
    public function caughtException() 
    {
        return $this->caughtException;
    }
    
    public function yieldTask(TaskInterface $task) 
    {
        $this->task = $task;
    }
    
    public function throwException(\Exception $exception) 
    {
        $this->exceptionToThrow = $exception;
    }
}
