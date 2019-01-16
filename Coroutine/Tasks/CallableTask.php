<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;

/**
 * Task that takes any callable object and runs it once. The result of running
 * the callable becomes the result of the task.
 */
class CallableTask extends AbstractTask 
{
    /**
     * The callable object that the task will run
     *
     * @var callable
     */
    protected $callable = null;
    
    /**
     * Create a new task from the given callable
     * 
     * @param callable $callable
     */
    public function __construct(callable $callable) 
	{
        $this->callable = $callable;
    }

    /**
     * {@inheritdoc}
     */
    protected function doTick(SchedulerInterface $scheduler) 
	{
        // On tick, we just run the callable and store the result
        try {
            $this->result = call_user_func($this->callable);
            $this->successful = true;
        }
        catch( \Exception $e ) {
            $this->exception = $e;
        }
    }
}
