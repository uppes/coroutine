<?php

namespace parallel;

use Async\ParallelInterface;
use Async\Spawn\LauncherInterface;
use parallel\FutureInterface;

interface RuntimeInterface
{
  /* Create */
  public function __construct();

  /**
   * Shall schedule task for execution in parallel, passing argv at execution time.
   *
   * @param \closure|null $task
   * @param mixed ...$argv
   *
   * @return FutureInterface
   */
  public function run(?\closure $task = null, ...$argv): FutureInterface;

  /**
   * Shall request that the runtime shutdown.
   *
   * @return void
   */
  public function close(): void;

  /**
   * Shall attempt to force the runtime to shutdown.
   *
   * @return void
   */
  public function kill(): void;

  public function getFuture(): LauncherInterface;

  public function getParallel(): ParallelInterface;
}
