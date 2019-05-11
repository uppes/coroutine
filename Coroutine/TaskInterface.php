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

    public function setResult($value);
    
    public function cancelled(): bool;

    public function done(): bool;

    public function result();
}
