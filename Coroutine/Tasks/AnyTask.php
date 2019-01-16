<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\Tasks\SomeTask;

/**
 * Task that waits for one of many tasks to complete before itself completing
 */
class AnyTask extends SomeTask 
{
    /**
     * Create a task that waits for any one of the given tasks to complete before
     * completing itself
     * 
     * The task result is the result of the completed task
     * 
     * If all the tasks fail, the task will be faulted and the exception will be
     * a {@see \Async\Task\MultipleFailureException} consisting of the combined
     * reasons for the failures
     * 
     * @param Task[] $tasks
     */
    public function __construct(array $tasks) {
        // Hard-code $howMany to be 1 in parent constructor
        parent::__construct($tasks, 1);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getResult() {
        $result = parent::getResult();
        
        // The result from the parent is an array of length 0 or 1, depending on
        // whether a task has succeeded or not
        // We want to return the value of the task that succeeded
        // We use reset to get the first value rather than $result[0] since keys
        // are preserved from the given task array
        return count($result) >= 1 ? reset($result) : null;
    }
}
