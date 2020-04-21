<?php

namespace Async\Coroutine;

/**
 * Provides a way for a task to interrupt itself and pass control back
 * to the scheduler, and allowing some other task to run.
 */
interface TaskInterface
{
    public function taskId(): ?int;

    public function taskType(string $type);

    public function sendValue($sendValue);

    public function setState(string $status);

    public function getState(): string;

    public function run();

    /**
     * Reset all `Task` data, and call `close` on any related stored `object` classes.
     */
    public function close();

    /**
     * Store custom state of the task.
     */
    public function customState($state = null);

    /**
     * Store custom `object` data of the task.
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
     * A flag that indicates the task is parallel child process.
     *
     * @return bool
     */
    public function isParallel(): bool;

    /**
     * A flag that indicates whether or not the sub process task has started.
     *
     * @return bool
     */
    public function isProcess(): bool;

    /**
     * A flag that indicates whether or not the task has an error.
     *
     * @return bool
     */
    public function isErred(): bool;

    /**
     * A flag that indicates whether or not the task has started.
     *
     * @return bool
     */
    public function isPending(): bool;

    /**
     * A flag that indicates whether or not the task was cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool;

    /**
     * A flag that indicates whether or not the a `parallel/process` task received a kill signal.
     *
     * @return bool
     */
    public function isSignaled(): bool;

    /**
     * A flag that indicates whether or not the task has run to completion.
     *
     * @return bool
     */
    public function isCompleted(): bool;

    public function isRescheduled(): bool;

    public function isFinished(): bool;

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
