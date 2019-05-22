<?php

namespace Async\Coroutine;

/**
 * Provides a way for a task to interrupt itself and pass control back 
 * to the scheduler, and allowing some other task to run.
 */
interface TaskInterface
{
    public function taskId();

    public function sendValue($sendValue);

    /**
     * Mark the task as done and set an exception.
     * 
     * @param \Exception
     */
    public function setException($exception);

    public function run();
    
    public function isFinished(): bool;

    public function setState(string $status);

    /**
     * A flag that indicates whether or not the task has an error.
     *
     * @return bool
     */     
    public function erred(): bool;

    /**
     * A flag that indicates whether or not the task has started.
     *
     * @return bool
     */
    public function pending(): bool;

    /**
     * A flag that indicates whether or not the task was cancelled.
     *
     * @return bool
     */
    public function cancelled(): bool;

    /**
     * A flag that indicates whether or not the task has run to completion.
     *
     * @return bool
     */
    public function completed(): bool;

    public function result();
}
