<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;

/**
 * Task that takes wraps another task, and schedules that task with the given
 * tick interval when scheduled itself
 * 
 * It completes when the wrapped task completes, and it's result is the result
 * of the wrapped task
 */
class ThrottledTask implements TaskInterface 
{
    /**
     * The task to throttle execution of
     *
     * @var Task
     */
    protected $wrapped = null;
    
    /**
     * The tick interval to use
     *
     * @var float
     */
    protected $tickInterval = null;
    
    /**
     * Indicates if the wrapped task has been scheduled
     *
     * @var boolean
     */
    protected $wrappedTaskScheduled = false;
    
    /**
     * Task that takes wraps another task, and schedules that task with the given
     * tick interval when scheduled itself
     * 
     * It completes when the wrapped task completes, and it's result is the result
     * of the wrapped task
     * 
     * @param Task $taskToThrottle
     * @param float $tickInterval
     */
    public function __construct(TaskInterface $taskToThrottle, $tickInterval) {
        $this->wrapped = $taskToThrottle;
        $this->tickInterval = $tickInterval;
    }

    /**
     * {@inheritdoc}
     */
    public function isComplete() {
        return $this->wrapped->isComplete();
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful() {
        return $this->wrapped->isSuccessful();
    }

    /**
     * {@inheritdoc}
     */
    public function isFaulted() {
        return $this->wrapped->isFaulted();
    }

    /**
     * {@inheritdoc}
     */
    public function isCancelled() {
        return $this->wrapped->isCancelled();
    }

    /**
     * {@inheritdoc}
     */
    public function getResult() {
        return $this->wrapped->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getException() {
        return $this->wrapped->getException();
    }

    /**
     * {@inheritdoc}
     */
    public function tick(SchedulerInterface $scheduler) {
        // All we do is schedule the wrapped task with no delay and the given
        // tick interval the first time tick is called
        if( !$this->wrappedTaskScheduled ) {
            $scheduler->schedule($this->wrapped, null, $this->tickInterval);
            $this->wrappedTaskScheduled = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancel() {
        $this->wrapped->cancel();
    }
}
