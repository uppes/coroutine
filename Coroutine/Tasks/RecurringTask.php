<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;

/**
 * Task that takes any callable object and runs it once per tick for the specified
 * number of times
 */
class RecurringTask extends AbstractTask 
{
    /**
     * The callable object that the task will run
     *
     * @var callable
     */
    protected $callable = null;
    
    /**
     * The number of times that the the task should be invoked
     *
     * @var integer
     */
    protected $times = 0;
    
    /**
     * The number of times that the tick method has been called
     *
     * @var integer
     */
    protected $ticks = 0;
    
    /**
     * Create a new task from the given callable
     * 
     * If $times is given and >= 0, it is the number of times that the task will be
     * invoked
     * If $times < 0, the task will be invoked forever
     * 
     * @param callable $callable
     * @param integer $times  $times < 0 means run forever
     */
    public function __construct(callable $callable, $times = -1) 
	{
        $this->callable = $callable;
        $this->times = $times;
    }
    
    public function isSuccessful() 
	{
        // We have completed successfully if the callable has been run enough times
        // times < 0 => run forever
        return $this->times >= 0 && $this->ticks >= $this->times;
    }

    /**
     * {@inheritdoc}
     */
    protected function doTick(SchedulerInterface $scheduler) 
	{
        // Increment the counter
        $this->ticks++;
        
        // On tick, we just run the callable, catching any exceptions
        try {
            call_user_func($this->callable);
        }
        catch( \Exception $e ) {
            $this->exception = $e;
        }
    }
}
