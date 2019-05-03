<?php

namespace Async\Coroutine;

use Async\Coroutine\TaskInterface;

interface CoroutineInterface 
{
    /**
     * Creates a new task (using the next free task id).
     * 
     * @param \Generator $coroutine
     * @return int task id
     */
    public function addTask(\Generator $coroutine);

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
    public function removeTask(int $tid);
    
    /**
     * Process/walk the task queue and runs the tasks.
     * If a task is finished it's dropped, otherwise rescheduled at the end of the queue.
     */
    public function run();

    /**
     * Adds a read stream.
     * 
     * @param resource $stream
     * @param callable $task
     */
    public function addReadStream($stream, $task);

    /**
     * Adds a write stream.
     * 
     * @param resource $stream
     * @param callable $task
     */
    public function addWriteStream($stream, $task);

    /**
     * Stop watching a stream for reads.
     * 
     * @param resource $stream
     */
    public function removeReadStream($stream);
    
    /**
     * Stop watching a stream for writes.
     * 
     * @param resource $stream
     */
    public function removeWriteStream($stream);

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
