<?php

declare(strict_types=1);

namespace Async\Coroutine;

use Async\Coroutine\Coroutine;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\CoroutineInterface;

/**
 * Channels provide a way for two coroutines to communicate with one another
 * and synchronize their execution.
 */
class Channel
{
    protected $targetTaskId;
    protected $buffer = null;
    protected $task = null;
    protected $coroutine = null;

    private function __construct(TaskInterface $task, CoroutineInterface $coroutine)
    {
        $this->task = $task;
        $this->coroutine = $coroutine;
    }

    /**
     * Creates an Channel similar to Google's Go language
     *
     * @return object
     */
    public static function make(TaskInterface $task, CoroutineInterface $coroutine)
    {
        return new self($task, $coroutine);
    }

    public function receiver(int $id)
    {
        $this->targetTaskId = $id;
    }

    public function receiverId(): int
    {
        return $this->targetTaskId;
    }

    public function senderTask(): TaskInterface
    {
        return $this->task;
    }

    public function receive()
    {
        $received = yield;
        yield Coroutine::value($received);
    }
}
