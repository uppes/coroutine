<?php

namespace Async\Coroutine;

use Async\Processor\LauncherInterface;

interface ParallelInterface
{
    /**
     * @return static
     */
    public static function create(): ParallelInterface;

    public function concurrency(int $concurrency): ParallelInterface;

    public function sleepTime(int $sleepTime);

    public function results(): array;

    public function isPcntl(): bool;

    public function status(): ParallelStatus;

    /**
     * @param LauncherInterface|callable $process
     * @param int $timeout
     * @return LauncherInterface
     */
    public function add($process, int $timeout = 300): LauncherInterface;

    public function retry(LauncherInterface $process = null): LauncherInterface;

    public function wait(): array;

    /**
     * @return LauncherInterface[]
     */
    public function getQueue(): array;

    public function markAsFinished(LauncherInterface $process);

    public function markAsTimedOut(LauncherInterface $process);

    public function markAsFailed(LauncherInterface $process);

    public function getFinished(): array;

    public function getFailed(): array;

    public function getTimeouts(): array;
}
