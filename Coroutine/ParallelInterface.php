<?php

namespace Async;

use Async\Spawn\Channeled;
use Async\Spawn\FutureInterface;

interface ParallelInterface
{
    public function concurrency(int $concurrency): ParallelInterface;

    public function sleepTime(int $sleepTime);

    public function results(): array;

    public function isPcntl(): bool;

    public function status(): ParallelStatus;

    /**
     * Reset all sub process data, and kill any running.
     */
    public function close();

    /**
     * @param Future|callable $process
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     *
     * @return FutureInterface
     */
    public function add($process, int $timeout = 0, $channel = null): FutureInterface;

    public function retry(FutureInterface $process = null): FutureInterface;

    public function wait(): array;

    /**
     * @return FutureInterface[]
     */
    public function getQueue(): array;

    public function markAsSignaled(FutureInterface $process);

    public function markAsFinished(FutureInterface $process);

    public function markAsTimedOut(FutureInterface $process);

    public function markAsFailed(FutureInterface $process);

    public function getFinished(): array;

    public function getFailed(): array;

    public function getTimeouts(): array;

    public function getSignaled(): array;
}
