<?php

declare(strict_types=1);

namespace Async\Coroutine;

use Async\Spawn\LauncherInterface;
use Async\Coroutine\Coroutine;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\Exceptions\InvalidStateError;

/**
 * Task is used to schedule coroutines concurrently.
 * When a coroutine is wrapped into a Task with functions like Coroutine::createTask()
 * the coroutine is automatically scheduled to run soon.
 *
 * This Task class can also be seen to operate like an Fiber according to the RFC spec https://wiki.php.net/rfc/fiber
 *
 * @see https://curio.readthedocs.io/en/latest/reference.html#tasks
 */
final class Task implements TaskInterface
{
    /**
     * The task’s id.
     *
     * @var int
     */
    protected $taskId;

    /**
     * A flag that indicates whether or not a task is daemonic.
     *
     * @var bool
     */
    protected $daemon;

    /**
     * The number of scheduling cycles the task has completed.
     * This might be useful if you’re trying to figure out if a task is running or not.
     * Or if you’re trying to monitor a task’s progress.
     *
     * @var int
     */
    protected $cycles = 0;

    /**
     * The underlying coroutine associated with the task.
     *
     * @var mixed
     */
    protected $coroutine;

    /**
     * The name of the task’s current state. Printing it can be potentially useful for debugging.
     *
     * @var string
     */
    protected $state = null;

    /**
     * The result of a task.
     *
     * @var mixed
     */
    protected $result;
    protected $sendValue = null;

    protected $beforeFirstYield = true;

    /**
     * Exception raised by a task, if any.
     *
     * @var object
     */
    protected $error;
    protected $exception = null;

    /**
     * Use to store custom state.
     *
     * This property is for third party code/library.
     *
     * @var mixed
     */
    protected $customState;

    /**
     * Use to store custom data.
     *
     * This property is for third party code/library.
     *
     * @var mixed
     */
    protected $customData;

    /**
     * A flag that indicates whether or not a task is an is parallel process.
     * Task type either `paralleled`, `yielded`, or `awaited`.
     *
     * @var string
     */
    protected $taskType = 'awaited';

    public function __construct($taskId, \Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->state = 'pending';
        $this->coroutine = Coroutine::create($coroutine);
    }

    public function close()
    {
        $object = $this->customData;
        if (\is_object($object) && \method_exists($object, 'close')) {
            $object->close();
        }

        $this->taskId = null;
        $this->daemon = null;
        $this->cycles = 0;
        $this->coroutine = null;
        $this->state = 'closed';
        $this->result = null;
        $this->sendValue = null;
        $this->beforeFirstYield = true;
        $this->error = null;
        $this->exception = null;
        $this->customState = null;
        $this->customData = null;
    }

    public function cyclesAdd()
    {
        $this->cycles++;
    }

    public function taskId(): ?int
    {
        return $this->taskId;
    }

    public function taskType(string $type)
    {
        $this->taskType = $type;
    }

    public function sendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }

    public function setException($exception)
    {
        $this->error = $this->exception = $exception;
    }

    public function setState(string $status)
    {
        $this->state = $status;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function customState($state = null)
    {
        $this->customState = $state;
    }

    public function customData($data = null)
    {
        $this->customData = $data;
    }

    public function getCustomState()
    {
        return $this->customState;
    }

    public function getCustomData()
    {
        return $this->customData;
    }

    public function exception(): ?\Exception
    {
        return $this->error;
    }

    public function isCustomState($state): bool
    {
        return ($this->customState === $state);
    }

    public function isParallel(): bool
    {
        return ($this->taskType == 'paralleled');
    }

    public function isProcess(): bool
    {
        return ($this->state == 'process');
    }

    public function isErred(): bool
    {
        return ($this->state == 'erred');
    }

    public function isPending(): bool
    {
        return ($this->state == 'pending');
    }

    public function isCancelled(): bool
    {
        return ($this->state == 'cancelled');
    }

    public function isSignaled(): bool
    {
        return ($this->state == 'signaled');
    }

    public function isCompleted(): bool
    {
        return ($this->state == 'completed');
    }

    public function isRescheduled(): bool
    {
        return ($this->state == 'rescheduled');
    }

    public function isFinished(): bool
    {
        return ($this->coroutine instanceof \Generator)
            ? !$this->coroutine->valid()
            : true;
    }

    public function result()
    {
        if ($this->isCompleted()) {
            $result = $this->result;
            if ($this->customData instanceof LauncherInterface)
                $data = $this->customData->getResult();
            $this->close();
            return isset($data) ? $data : $result;
        } elseif ($this->isCancelled() || $this->isErred() || $this->isSignaled()) {
            $error = $this->exception();
            $message = $error->getMessage();
            $code = $error->getCode();
            $throwable = $error->getPrevious();
            $class = \get_class($error);
            $message = \str_replace('The operation has been cancelled, with: ', '', $message);
            $message = \str_replace('The operation has exceeded the given deadline: ', '', $message);
            $message = \str_replace('Coroutine task has erred: ', '', $message);
            $message = \str_replace('Invalid internal state called on: ', '', $message);
            $this->close();
            return new $class($message, $code, $throwable);
        } else {
            $this->close();
            throw new InvalidStateError();
        }
    }

    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return ($this->coroutine instanceof \Generator)
                ? $this->coroutine->current()
                : null;

        } elseif ($this->exception) {
            $value = ($this->coroutine instanceof \Generator)
                ? $this->coroutine->throw($this->exception)
                : $this->exception;

            $this->error = $this->exception;
            $this->exception = null;
            return $value;
        } else {
            $value = ($this->coroutine instanceof \Generator)
                ? $this->coroutine->send($this->sendValue)
                : $this->sendValue;

            if (!empty($value)) {
                $this->result = $value;
            }

            $this->sendValue = null;
            return $value;
        }
    }
}
