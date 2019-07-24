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

    /**
     * Return the result of the Task.
     *
     * - If the Task is done, the result of the wrapped coroutine is returned
     * (or if the coroutine raised an exception, that exception is re-raised.)
     *
     * - If the Task has been cancelled, this method raises a `CancelledError` exception.
     *
     * - If the Task’s result isn’t yet available, this method raises a `InvalidStateError` exception.
     *
     * @see https://docs.python.org/3/library/asyncio-task.html#asyncio.Task.result
     */
    public function result();

    /**
     * Return the exception of the Task.
     *
     * - If the wrapped coroutine raised an exception that exception is returned.
     *
     * - If the wrapped coroutine returned normally this method returns `null`.
     *
     * - If the Task has been cancelled, this method raises a `CancelledError` exception.
     *
     * - If the Task isn’t done yet, this method raises an `InvalidStateError` exception.
     *
     * @see https://docs.python.org/3/library/asyncio-task.html#asyncio.Task.exception
     */
    public function exception(): \Exception;
}
