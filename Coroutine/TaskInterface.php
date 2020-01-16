<?php

namespace Async\Coroutine;

/**
 * Provides a way for a task to interrupt itself and pass control back
 * to the scheduler, and allowing some other task to run.
 */
interface TaskInterface
{
    public function taskId();

    public function taskType(string $type);

    public function sendValue($sendValue);

    public function setState(string $status);

    public function getState(): string;

    public function run();

    public function isFinished(): bool;

    public function parallelTask();

    /**
     * Store custom state of the task.
     */
    public function customState($state = null);

    /**
     * Store custom data of the task.
     */
    public function customData($data = null);

    /**
     * Return the stored custom state of the task.
     */
    public function getCustomState();

    /**
     * Return the stored custom data of the task.
     */
    public function getCustomData();

    /**
     * A flag that indicates custom state is as requested.
     *
     * @return bool
     */
    public function isCustomState($state): bool;

    /**
     * A flag that indicates whether or not the sub process task has started.
     *
     * @return bool
     */
    public function process(): bool;

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
     * Mark the task as done and set an exception.
     *
     * @param \Exception
     */
    public function setException($exception);

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
    public function exception(): ?\Exception;
}
