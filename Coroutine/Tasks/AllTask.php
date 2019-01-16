<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\Tasks\SomeTask;

/**
 * Task that waits for all the given tasks to complete before itself completing
 */
class AllTask extends SomeTask 
{
    /**
     * Create a task that waits for all of the given tasks to complete before
     * completing itself
     * 
     * The task result will be an array containing the results of the tasks, with
     * keys preserved from the $tasks array
     * 
     * If one of the given tasks fails, the task will be faulted, and the exception
     * will be a {@see \Async\Task\MultipleFailureException} containing the exception
     * that the first failed task failed with. This is so that the failed task
     * can be identified by looking at the set key in getFailures
     * 
     * @param Task[] $tasks
     */
    public function __construct(array $tasks) {
        // Hard-code $howMany to be the number of tasks in parent constructor
        parent::__construct($tasks, count($tasks));
    }
}
