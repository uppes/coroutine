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

    public function exception($exception);

    public function run();

    public function isFinished();
}
