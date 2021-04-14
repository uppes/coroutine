<?php

declare(strict_types=1);

namespace Async;

use Async\Coroutine;
use Async\TaskInterface;
use Async\CoroutineInterface;

/**
 * Channels provide a way for two coroutines to communicate with one another
 * and synchronize their execution.
 */
final class Channel
{
    protected $targetTask;
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

    public function receiver(TaskInterface $task)
    {
        $this->targetTask = $task;
    }

    public function receiverTask(): ?TaskInterface
    {
        return $this->targetTask;
    }

    public function senderTask(): TaskInterface
    {
        return $this->task;
    }

    /**
     * @codeCoverageIgnore
     */
    public function receive()
    {
        $received = yield;
        return yield Coroutine::value($received);
    }
}
