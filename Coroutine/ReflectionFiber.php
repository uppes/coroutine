<?php

namespace  Async\Coroutine;

use ReflectionGenerator;
use Async\Coroutine\Fiber;
use Async\Coroutine\FiberError;

class ReflectionFiber
{
    /**
     * @var array
     */
    protected $reflection = [];

    /**
     * @var Fiber
     */
    protected $fiber = null;

    /**
     * @param Fiber $fiber Any Fiber object, including those that are not started or have
     *                     terminated.
     */
    public function __construct(Fiber $fiber)
    {
        $this->fiber = $fiber;
    }

    /**
     * @return string Current file of fiber execution.
     */
    public function getExecutingFile(): string
    {
        try {
            if ($this->fiber->getGenerator() instanceof \Generator) {
                return (new ReflectionGenerator($this->fiber->getGenerator()))->getExecutingFile();
            }
        } catch (\ReflectionException $e) {
        }

        throw new FiberError('Cannot fetch information from a fiber that has not been started or is terminated');
    }

    /**
     * @return int Current line of fiber execution.
     */
    public function getExecutingLine(): int
    {
        try {
            if ($this->fiber->getGenerator() instanceof \Generator) {
                return (new ReflectionGenerator($this->fiber->getGenerator()))->getExecutingLine();
            }
        } catch (\ReflectionException $e) {
        }

        throw new FiberError('Cannot fetch information from a fiber that has not been started or is terminated');
    }

    /**
     * @param int $options Same flags as {@see debug_backtrace()}.
     *
     * @return array Fiber backtrace, similar to {@see debug_backtrace()}
     *               and {@see ReflectionGenerator::getTrace()}.
     */
    public function getTrace(int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT): array
    {
        try {
            if ($this->fiber->getGenerator() instanceof \Generator) {
                $this->reflection = (new ReflectionGenerator($this->fiber->getGenerator()))->getTrace($options);
                $json = \json_encode(\print_r($this->fiber, true));
                return \array_merge($this->reflection, (array) \json_decode($json, true));
            }
        } catch (\ReflectionException $e) {
        }

        throw new FiberError('Cannot fetch information from a fiber that has not been started or is terminated');
    }

    /**
     * @return bool True if the fiber has been started.
     */
    public function isStarted(): bool
    {
        return $this->fiber->isStarted();
    }

    /**
     * @return bool True if the fiber is currently suspended.
     */
    public function isSuspended(): bool
    {
        return $this->fiber->isSuspended();
    }

    /**
     * @return bool True if the fiber is currently running.
     */
    public function isRunning(): bool
    {
        return $this->fiber->isRunning();
    }

    /**
     * @return bool True if the fiber has completed execution (either returning or
     *              throwing an exception), false otherwise.
     */
    public function isTerminated(): bool
    {
        return $this->fiber->isTerminated();
    }

    public function getFiber(): Fiber
    {
        return $this->fiber;
    }
}
