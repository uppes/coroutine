<?php
namespace Async\Coroutine;

use Async\Coroutine\Tasks\TaskInterface;

interface SchedulerInterface 
{
    /**
     * Schedule a task to be run, with an optional delay before starting and
     * minimum interval between ticks
     * 
     * NOTE: $delay and $tickInterval may not be supported by all Scheduler
     *       implementations. If they are not supported, an exception is thrown
     *       if an attempt is made to use them.
     * 
     * @param Task $task
     * @param float $delay OPTIONAL
     * @param float $tickInterval OPTIONAL
     */
    public function schedule(TaskInterface $task);
    
    /**
     * Run until there is no more work to do, or until stop is called
     */
    public function run();
}
