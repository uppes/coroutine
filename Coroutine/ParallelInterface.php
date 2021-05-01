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
   * Reset all sub `future` data, and kill any running.
   */
  public function close();

  /**
   * Create an `yield`able Future `sub/child` **task**, that can include an additional **file**.
   * This function exists to give same behavior as **parallel\runtime** of `ext-parallel` extension,
   * but without any of the it's limitations. All child output is displayed.
   * - This feature is for `Coroutine` package or any third party package using `yield` for execution.
   *
   * @param closure $future
   * @param string $include additional file to execute
   * @param Channeled|mixed|null ...$args - if a `Channel` instance is passed, it wil be used to set `Future` **IPC/CSP** handler
   *
   * @return FutureInterface
   * @see https://www.php.net/manual/en/parallel.run.php
   */
  public function adding(?\closure $future = null, ?string $include = null, ...$args): FutureInterface;

  /**
   * @param Future|callable $future
   * @param int|float|null $timeout The timeout in seconds or null to disable
   * @param Channeled|resource|mixed|null $channel IPC/CSP communication to be pass to the underlying `Future` instance.
   *
   * @return FutureInterface
   */
  public function add($future, int $timeout = 0, $channel = null): FutureInterface;

  public function retry(FutureInterface $future = null): FutureInterface;

  public function wait(): array;

  /**
   * @return FutureInterface[]
   */
  public function getQueue(): array;

  public function markAsSignaled(FutureInterface $future);

  public function markAsFinished(FutureInterface $future);

  public function markAsTimedOut(FutureInterface $future);

  public function markAsFailed(FutureInterface $future);

  public function getFinished(): array;

  public function getFailed(): array;

  public function getTimeouts(): array;

  public function getSignaled(): array;
}
