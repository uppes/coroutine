<?php

namespace Async\Coroutine;

use Async\Coroutine\Coroutine;
use Async\Coroutine\FiberInterface;
use Throwable;

/**
 * This `Fiber` has same behavior as `Task` class, with exception in that it's directly usable/callable by the user.
 *
 * It has been added to address and simulate the new **unneeded** `RFC` https://wiki.php.net/rfc/fibers.
 *
 * @codeCoverageIgnore
 */
final class Fiber implements FiberInterface
{
    /**
     * @var TaskInterface|FiberInterface|null
     */
    protected $taskFiber;
    protected $taskType = 'fiber';

    /**
     * The number of scheduling cycles the fiber has completed.
     * This might be useful if you’re trying to figure out if a fiber is running or not.
     * Or if you’re trying to monitor a fiber’s progress.
     *
     * @var int
     */
    protected $cycles = 0;

    /**
     * The fiber id.
     *
     * @var int
     */
    protected $fiberId;

    /**
     * A indicator of current file of fiber execution.
     *
     * @var string
     */
    protected $currentFile;

    /**
     * A indicator of current line of fiber execution.
     *
     * @var int
     */
    protected $currentLine;

    /**
     * Fiber backtrace.
     *
     * @var array
     */
    protected $trace;

    /**
     * The underlying coroutine associated with `Fiber`.
     *
     * @var mixed
     */
    protected $coroutine;

    /**
     * @var Coroutine
     */
    protected $scheduler;

    /**
     * The name of the fiber’s current `status` state.
     *
     * @var string
     */
    protected $state = null;

    /**
     * The result of a fiber.
     *
     * @var mixed
     */
    protected $result;
    protected $sendValue = null;

    /**
     * A flag that indicates the `fiber` has started.
     *
     * @var bool
     */
    protected $fiberStarted = false;

    /**
     * Exception raised by a fiber, if any.
     *
     * @var object
     */
    protected $error;
    protected $exception = null;

    /**
     * Invoke when starting the fiber.
     *
     * @var callable
     */
    protected $callback;

    /**
     * @var FiberInterface|null
     */
    protected static $fiber = null;

    /**
     * @param callable $callback Function to invoke when starting the fiber.
     */
    public function __construct(callable $callback)
    {
        self::$fiber = $this;
        $this->callback = $callback;
        $this->state = 'pending';
        $this->scheduler = \coroutine_create();
        $this->fiberId = $this->scheduler->addFiber($this);
    }

    public function start(...$args)
    {
        $fiberTask = awaitable($this->callback, ...$args);
        $this->coroutine = Coroutine::create($fiberTask);
        return yield Kernel::startFiber($this);
    }

    public static function suspend($value = null)
    {
        return yield Kernel::suspendFiber($value);
    }

    public function resume($value = null)
    {
        return yield Kernel::resumeFiber($this, $value);
    }

    public function throw(Throwable $exception)
    {
        return yield Kernel::throwFiber($this, $exception);
    }

    public static function this(): ?FiberInterface
    {
        return self::$fiber;
    }

    public function run()
    {
        if (!$this->fiberStarted) {
            $this->fiberStarted = true;
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

            if (!empty($value))
                $this->result = $value;

            $this->sendValue = null;
            return $value;
        }
    }

    public function close()
    {
        self::$fiber = null;
        $this->callback = null;
        $this->fiberId = null;
        $this->cycles = 0;
        $this->coroutine = null;
        $this->state = 'closed';
        $this->result = null;
        $this->sendValue = null;
        $this->fiberStarted = false;
        $this->error = null;
        $this->exception = null;
        $this->scheduler = null;
        $this->taskFiber = null;
        $this->currentFile = null;
        $this->currentLine = null;
        $this->trace = null;
    }

    public function setState(string $status)
    {
        $this->state = $status;
    }

    public function setTaskFiber($taskFiber)
    {
        $this->taskFiber = $taskFiber;
    }

    public function getTaskFiber()
    {
        return $this->taskFiber;
    }

    public function setException($exception)
    {
        $this->error = $this->exception = $exception;
    }

    public function sendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }

    /**
     * Add to counter of the cycles the task has run.
     *
     * @return void
     */
    public function cyclesAdd()
    {
        $this->cycles++;
    }

    /**
     * Return the number of times the scheduled task has run.
     *
     * @return int
     */
    public function getCycles()
    {
        return $this->cycles;
    }

    public function fiberId(): ?int
    {
        return $this->fiberId;
    }

    public function isStarted(): bool
    {
        return $this->fiberStarted;
    }

    public function isSuspended(): bool
    {
        return ($this->state === 'suspended');
    }

    public function isRunning(): bool
    {
        return ($this->state === 'running');
    }

    public function isTerminated(): bool
    {
        return ($this->state === 'completed');
    }

    public function isErred(): bool
    {
        return ($this->state === 'erred');
    }

    public function isCompleted(): bool
    {
        return $this->isTerminated();
    }

    public function isRescheduled(): bool
    {
        return ($this->state === 'rescheduled');
    }

    public function isPending(): bool
    {
        return ($this->state === 'pending');
    }

    public function isNetwork(): bool
    {
        return ($this->taskType == 'networked');
    }

    public function isFinished(): bool
    {
        return ($this->coroutine instanceof \Generator)
            ? !$this->coroutine->valid()
            : true;
    }

    public function result()
    {
        return $this->getReturn();
    }

    public function getReturn()
    {
        if ($this->isTerminated()) {
            $result = $this->result;
            $this->close();
            return !empty($result) ? $result : null;
        } elseif ($this->isRunning() || $this->isSuspended() || $this->isErred()) {
            $error = $this->error;
            if (empty($error))
                $error = new FiberError("Internal operation stoppage, all data reset.");

            $message = $error->getMessage();
            $code = $error->getCode();
            $throwable = $error->getPrevious();
            $class = \get_class($error);
            $this->close();
            return new $class($message, $code, $throwable);
        } else {
            // @codeCoverageIgnoreStart
            $this->close();
            throw new FiberExit('Invalid internal state called');
            // @codeCoverageIgnoreEnd
        }
    }
}
