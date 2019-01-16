<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;

/**
 * Task that takes wraps another task, and schedules that task with the given
 * delay when scheduled itself
 * 
 * It completes when the wrapped task completes, and it's result is the result
 * of the wrapped task
 */
class DelayedTask implements TaskInterface 
{
    /**
     * The task to delay execution of
     *
     * @var \Async\Task\Task
     */
    protected $wrapped = null;
    
    /**
     * The delay to run with
     *
     * @var float
     */
    protected $delay = null;
    
    /**
     * Indicates if the wrapped task has been scheduled
     *
     * @var boolean
     */
    protected $wrappedTaskScheduled = false;
    
    /**
     * Task that takes wraps another task, and schedules that task with the given
     * delay when scheduled itself
     * 
     * It completes when the wrapped task completes, and it's result is the result
     * of the wrapped task 
     * 
     * @param \Async\Task\Task $taskToDelay
     * @param float $delay
     */
    public function __construct(TaskInterface $taskToDelay, $delay) 
	{
        $this->wrapped = $taskToDelay;
        $this->delay = $delay;
    }

    /**
     * {@inheritdoc}
     */
    public function isComplete() 
	{
        return $this->wrapped->isComplete();
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful() 
	{
        return $this->wrapped->isSuccessful();
    }

    /**
     * {@inheritdoc}
     */
    public function isFaulted() 
	{
        return $this->wrapped->isFaulted();
    }

    /**
     * {@inheritdoc}
     */
    public function isCancelled() 
	{
        return $this->wrapped->isCancelled();
    }

    /**
     * {@inheritdoc}
     */
    public function getResult() 
	{
        return $this->wrapped->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getException() 
	{
        return $this->wrapped->getException();
    }

    /**
     * {@inheritdoc}
     */
    public function tick(SchedulerInterface $scheduler) 
	{
        // All we do is schedule the wrapped task with a delay the first time
        // tick is called
        if( !$this->wrappedTaskScheduled ) {
            $scheduler->schedule($this->wrapped, $this->delay);
            $this->wrappedTaskScheduled = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancel() 
	{
        $this->wrapped->cancel();
    }
}
