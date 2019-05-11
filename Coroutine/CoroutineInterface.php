<?php

namespace Async\Coroutine;

use Async\Coroutine\TaskInterface;

interface CoroutineInterface 
{
    /**
     * Creates a new task (using the next free task id).
     * wraps coroutine into a Task and schedule its execution. Return the Task object/id.
     * 
     * @see https://docs.python.org/3.7/library/asyncio-task.html#creating-tasks
     * 
     * @param \Generator $coroutine
     * @return int task id
     */
    public function createTask(\Generator $coroutine);

    /**
     * Add an new task into the running task queue.
     * 
     * @param TaskInterface $task
     */
    public function schedule(TaskInterface $task);

	/**
	 * kill/remove an task using task id
	 * 
	 * @param int $tid
	 * @return bool
	 */
    public function cancelTask(int $tid);
    
    /**
     * Process/walk the task queue and runs the tasks.
     * If a task is finished it's dropped, otherwise rescheduled at the end of the queue.
     * 
     * @see https://docs.python.org/3.7/library/asyncio-task.html#running-an-asyncio-program
     */
    public function run();

    /**
     * Adds a read stream to start monitoring the
     * stream/file descriptor for read availability and invoke callback
     * once stream is available for reading.
     * 
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.add_reader
     * 
     * @param resource $stream
     * @param callable $task
     */
    public function addReader($stream, $task);

    /**
     * Adds a write stream to start monitoring the 
     * stream/file descriptor for write availability and invoke callback
     * once stream is available for writing.
     * 
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.add_writer
     * 
     * @param resource $stream
     * @param callable $task
     */
    public function addWriter($stream, $task);

    /**
     * Stop monitoring the stream/file descriptor for read availability.
     * 
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.remove_reader
     * 
     * @param resource $stream
     */
    public function removeReader($stream);
    
    /**
     * Stop monitoring the stream/file descriptor for write availability.
     * 
     * @see https://docs.python.org/3.7/library/asyncio-eventloop.html#asyncio.loop.remove_writer
     * @param resource $stream
     */
    public function removeWriter($stream);

    /**
     * Executes a function after x seconds.
     * 
     * @param callable $task
     * @param float $timeout
     */
    public function addTimeout($task, float $timeout);

    /**
     * Executes a function every x seconds.
     * 
     * @param callable $task
     * @param float $timeout
     */
    public function setInterval($task, float $timeout): array;

    /**
     * Stops a running interval.
     */
    public function clearInterval(array $intervalId);

    /**
     * Creates an object instance of the value which will signal 
     * `Coroutine::create` that it’s a return value. 
     * 
     *  - yield Coroutine::value("I'm a return value!");
     * 
     * @param mixed $value
     * @return ReturnValueCoroutine
     */
    public static function value($value);
    
    /**
     * Create and manage a stack of nested coroutine calls. This allows turning 
     * regular functions/methods into sub-coroutines just by yielding them.
     * 
     *  - $value = (yield functions/methods($foo, $bar));
     * 
     * @param \Generator $gen
     */
    public static function create(\Generator $gen);
}
