<?php

use Async\Coroutine\Coroutine;

/**
 * @codeCoverageIgnore
 */
final class Fiber implements FiberInterface
{
    protected $incomingTask;
    protected $outgoingTask;

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
     * The name of the fiber’s current state.
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
     * Exception raised by a task, if any.
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
        $this->scheduler->scheduleFiber($this);
        $this->scheduler->run();
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

    public function setState(string $status)
    {
        $this->state = $status;
    }

    public function setException($exception)
    {
        $this->error = $this->exception = $exception;
    }

    public function sendValue($sendValue)
    {
        $this->sendValue = $sendValue;
    }

    public function resume($value = null)
    {
    }

    public function throw(Throwable $exception)
    {
    }

    public function isStarted(): bool
    {
        return $this->fiberStarted;
    }

    public function isSuspended(): bool
    {
        return ($this->state == 'suspended');
    }

    public function isRunning(): bool
    {
        return ($this->state == 'running');
    }

    public function isTerminated(): bool
    {
        return ($this->state == 'completed');
    }

    public function isErred(): bool
    {
        return ($this->state == 'erred');
    }

    public function getReturn()
    {
        if ($this->isTerminated()) {
            $result = $this->result;
            $this->result = null;
            return !empty($result) ? $result : null;
        } elseif ($this->isRunning() || $this->isSuspended() || $this->isErred()) {
            $error = $this->error;
            $this->error = null;
            if (empty($error))
                $error = new FiberError("Internal operation stoppage, all data reset.");

            $message = $error->getMessage();
            $code = $error->getCode();
            $throwable = $error->getPrevious();
            $class = \get_class($error);
            return new $class($message, $code, $throwable);
        } else {
            // @codeCoverageIgnoreStart
            throw new FiberExit('Invalid internal state called');
            // @codeCoverageIgnoreEnd
        }
    }

    public static function this(): ?self
    {
        return self::$fiber;
    }

    public static function suspend($value = null)
    {
    }
}
