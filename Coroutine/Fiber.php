<?php

namespace Async\Coroutine;

use Async\Coroutine\Coroutine;
use Async\Coroutine\FiberInterface;
use Async\Coroutine\FiberError;
use Async\Coroutine\FiberExit;
use Throwable;

/**
 * This `Fiber` has same behavior as `Task` class, with exception in that it's directly usable/callable by the user.
 *
 * It has been added to address and polyfill `RFC` https://wiki.php.net/rfc/fibers.
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
     * The underlying coroutine associated with `Fiber`.
     *
     * @var mixed
     */
    protected $coroutine;

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
    protected $finishResult;
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

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param callable $callback Function to invoke when starting the fiber.
     */
    public function __construct(callable $callback)
    {
        self::$fiber = $this;
        $this->callback = $callback;
        $this->state = 'pending';
        $this->fiberId = \coroutine_create()->addFiber($this);
    }

    public function start(...$args)
    {
        if ($this->isStarted())
            throw new FiberError('Cannot start a fiber that has already been started in ' . __FILE__);

        $data = &$args;
        $this->coroutine = Coroutine::create(\awaitable($this->callback, ...$data));
        return yield Kernel::startFiber($this);
    }

    public static function suspend($value = null)
    {
        $data = &$value;
        return yield Kernel::suspendFiber($data);
    }

    public function resume($value = null)
    {
        if (!$this->isSuspended())
            throw new FiberError('Cannot resume a fiber that is not suspended in ' . __FILE__);

        $data = &$value;
        return yield Kernel::resumeFiber($this, $data);
    }

    public function throw(Throwable $exception)
    {
        if (!$this->isSuspended())
            throw new FiberError('Cannot resume a fiber that is not suspended in ' . __FILE__);

        $error = &$exception;
        return yield Kernel::throwFiber($this, $error);
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

            if (!\is_null($value))
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
        $this->taskFiber = null;
        $this->finishResult = null;
    }

    public function getGenerator()
    {
        return $this->coroutine;
    }

    public function setReturn($value)
    {
        $this->finishResult = $value;
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

    /**
     * Return the exception of the fiber.
     *
     * @return \Exception
     *
     * @internal
     * @codeCoverageIgnore
     */
    public function exception(): ?\Exception
    {
        return $this->error;
    }

    public function sendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }

    /**
     * Add to counter of the cycles the fiber has run.
     *
     * @return void
     */
    public function cyclesAdd()
    {
        $this->cycles++;
    }

    /**
     * Return the number of times the scheduled fiber has run.
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

    public function getReturn()
    {
        if ($this->isTerminated()) {
            return !empty($this->result) ? $this->result : $this->finishResult;
        } elseif (!$this->isStarted() || $this->isActive() || $this->isErred()) {
            $error = $this->error;
            if ($this->isActive()) {
                throw new FiberError("Cannot get fiber return value: The fiber has not returned in " . __FILE__);
            } elseif ($this->isErred()) {
                throw new FiberError("Cannot get fiber return value: The fiber threw an exception in " . $error->getMessage(), 0, $error);
            } elseif (!$this->isStarted()) {
                throw new FiberError("Cannot get fiber return value: The fiber has not been started in " . __FILE__);
            }
        } else {
            throw new FiberExit('Invalid internal state called.');
        }
    }

    public function isErred(): bool
    {
        return ($this->state === 'erred');
    }

    public function isRescheduled(): bool
    {
        return ($this->state === 'rescheduled');
    }

    public function isActive(): bool
    {
        return ($this->isRunning() || $this->isRescheduled() || $this->isSuspended());
    }

    public function isFinished(): bool
    {
        return ($this->coroutine instanceof \Generator)
            ? !$this->coroutine->valid()
            : true;
    }
}
