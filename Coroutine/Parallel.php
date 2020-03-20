<?php

declare(strict_types=1);

namespace Async\Coroutine;

use ArrayAccess;
use InvalidArgumentException;
use Async\Coroutine\ParallelStatus;
use Async\Coroutine\ParallelInterface;
use Async\Coroutine\CoroutineInterface;
use Async\Spawn\Spawn;
use Async\Spawn\LauncherInterface;

/**
 * @internal
 */
final class Parallel implements ArrayAccess, ParallelInterface
{
    private $coroutine = null;
    private $processor = null;
    private $status;
    private $process;
    private $concurrency = 20;
    private $queue = [];
    private $results = [];
    private $finished = [];
    private $failed = [];
    private $timeouts = [];
    private $parallel = [];

    public function __construct(CoroutineInterface $coroutine = null)
    {
        $this->coroutine = empty($coroutine) ? \coroutine_instance() : $coroutine;
        if (!$this->coroutine instanceof CoroutineInterface) {
            $this->coroutine = \coroutine_create();
        }

        $this->processor = $this->coroutine->getProcess(
            [$this, 'markAsTimedOut'],
            [$this, 'markAsFinished'],
            [$this, 'markAsFailed']
        );

        $this->status = new ParallelStatus($this);
    }

    /**
     * @return static
     */
    public static function create(): ParallelInterface
    {
        return new static();
    }

    public function concurrency(int $concurrency): ParallelInterface
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    public function sleepTime(int $sleepTime)
    {
        $this->processor->sleepTime($sleepTime);
    }

    public function results(): array
    {
        return $this->results;
    }

    public function isPcntl(): bool
    {
        return $this->processor->isPcntl();
    }

    public function status(): ParallelStatus
    {
        return $this->status;
    }

    public function add($process, int $timeout = 300, $channel = null): LauncherInterface
    {
        if (!is_callable($process) && !$process instanceof LauncherInterface) {
            throw new InvalidArgumentException('The process passed to Parallel::add should be callable.');
        }

        if (!$process instanceof LauncherInterface) {
            $process = Spawn::create($process, $timeout, $channel, true);
        }

        $this->putInQueue($process);

        $this->parallel[] = $this->process = $process;

        return $process;
    }

    private function notify($restart = false)
    {
        if ($this->processor->count() >= $this->concurrency) {
            return;
        }

        $process = \array_shift($this->queue);

        if (!$process) {
            return;
        }

        $this->putInProgress($process, $restart);
    }

    public function retry(LauncherInterface $process = null): LauncherInterface
    {
        $this->putInQueue((empty($process) ? $this->process : $process), true);

        return $this->process;
    }

    public function wait(): array
    {
        while (true) {
            $this->coroutine->run();
            if ($this->processor->isEmpty())
                break;
        }

        return $this->results;
    }

    /**
     * @return LauncherInterface[]
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    private function putInQueue(LauncherInterface $process, $restart = false)
    {
        $this->queue[$process->getId()] = $process;

        $this->notify($restart);
    }

    private function putInProgress(LauncherInterface $process, $restart = false)
    {
        unset($this->queue[$process->getId()]);

        if ($restart) {
            $process = $process->restart();
            $this->process = $process;
        } else {
            $process->start();
        }

        $this->processor->add($process);
    }

    public function markAsFinished(LauncherInterface $process)
    {
        $this->notify();

        $this->results[] = yield from $process->triggerSuccess(true);

        $this->finished[$process->getPid()] = $process;
    }

    public function markAsTimedOut(LauncherInterface $process)
    {
        $this->notify();

        yield $process->triggerTimeout(true);

        $this->timeouts[$process->getPid()] = $process;
    }

    public function markAsFailed(LauncherInterface $process)
    {
        $this->notify();

        yield $process->triggerError(true);

        $this->failed[$process->getPid()] = $process;
    }

    public function getFinished(): array
    {
        return $this->finished;
    }

    public function getFailed(): array
    {
        return $this->failed;
    }

    public function getTimeouts(): array
    {
        return $this->timeouts;
    }

    public function offsetExists($offset)
    {
        return isset($this->parallel[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->parallel[$offset]) ? $this->parallel[$offset] : null;
    }

    public function offsetSet($offset, $value, int $timeout = 300)
    {
        $this->add($value, $timeout);
    }

    public function offsetUnset($offset)
    {
        $this->processor->remove($this->parallel[$offset]);
        unset($this->parallel[$offset]);
    }
}
