<?php

namespace Async\Coroutine\Tasks;

use Generator;
use Async\Coroutine\Co;
use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Coroutine\Tasks\AbstractTask;

class GeneratorTask extends AbstractTask 
{
    /**
     * The generator we are using
     *
     * @var \Generator
     */
    protected $generator = null;
    
    /**
     * The task that the generator is paused waiting for
     *
     * @var Task
     */
    protected $waiting = null;
    
    /**
     * Creates a new task using the given generator
     * 
     * @param \Generator $generator
     */
    public function __construct(Generator $generator) 
	{
        $this->generator = $generator;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isSuccessful() 
	{
        // We have successfully completed if the generator has no more items
        // A generator is successful if it is no more valid and if the task it
        // returned (if any) is also successful
        if (!$this->generator->valid()) {
        	if (!$this->waiting) {
        		return true;
        	}
        	return $this->waiting->isSuccessful();
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult() 
	{
    	if (!( $this->result instanceof TaskInterface )) {
    		return $this->result;
    	} else {
    		return $this->result->getResult();
    	}
    	 
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doTick(SchedulerInterface $scheduler) 
	{
        // Check if we are waiting for a task to complete in order to pass the
        // result to our generator
        if( $this->waiting ) {
            if( $this->waiting->isComplete() ) {
                // If the task we are waiting for is complete, we need to resume
                // the generator, passing the result of running the task
                // We want to catch any errors that are thrown
                try {
                    if( $this->waiting->isFaulted() ) {
                        // If the task we are waiting for has failed, throw it's
                        // exception into the generator
                        $this->generator->throw($this->waiting->getException());
                    }
                    else if( $this->waiting->isCancelled() ) {
                        // If the task we are waiting for was cancelled, throw a
                        // TaskCancelledException into the generator
                        $this->generator->throw(new TaskCancelledException($this->waiting));
                    }
                    else {
                        // Otherwise, send the result back
                        $this->generator->send($this->waiting->getResult());
                    }
                }
                catch( \Exception $e ) {
                    $this->exception = $e;
                }
            }
            else {
                // If the task we are waiting for is not complete, there is nothing
                // to do on this tick
                return;
            }
            
            // If we get this far, we are done waiting for that task
            $this->waiting = null;
        }
        
        // If we are complete, there is nothing more to do
        if( $this->isComplete() ) return;
        
        // Otherwise, we wait on the yielded task to complete
        $this->waiting = $this->generator->current();
        
		// Let's wrap the returned value to make it a task.        
        if (!($this->waiting instanceof TaskInterface)) {
        	$this->waiting = Co::async($this->waiting);
        }
        // Let's store the result.
        $this->result = $this->waiting;
        
        // Schedule the task we are waiting on with the scheduler
        $scheduler->schedule($this->waiting);
    }
}
