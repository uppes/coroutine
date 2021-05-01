<?php

declare(strict_types=1);

namespace Async;

use Async\Parallel;
use Async\Spawn\Future;
use Async\Spawn\SerializableException;

final class ParallelStatus
{
    protected $parallelPool;

    public function __construct(Parallel $parallelPool)
    {
        $this->parallelPool = $parallelPool;
    }

    public function __toString(): string
    {
        return $this->lines(
            $this->summaryToString(),
            $this->failedToString()
        );
    }

    protected function lines(string ...$lines): string
    {
        return \implode(\PHP_EOL, $lines);
    }

    protected function summaryToString(): string
    {
        $queue = $this->parallelPool->getQueue();
        $finished = $this->parallelPool->getFinished();
        $failed = $this->parallelPool->getFailed();
        $timeouts = $this->parallelPool->getTimeouts();
        $signaled = $this->parallelPool->getSignaled();

        return
            'queue: ' . \count($queue)
            . ' - finished: ' . \count($finished)
            . ' - failed: ' . \count($failed)
            . ' - timeout: ' . \count($timeouts)
            . ' - signaled: ' . \count($signaled);
    }

    protected function failedToString(): string
    {
        return (string) \array_reduce($this->parallelPool->getFailed(), function ($currentStatus, Future $future) {
            $output = $future->getErrorOutput();

            if ($output instanceof SerializableException) {
                $output = \get_class($output->asThrowable()) . ': ' . $output->asThrowable()->getMessage();
            }

            return $this->lines((string) $currentStatus, "{$future->getPid()} failed with {$output}");
        });
    }
}
