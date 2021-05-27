<?php

namespace parallel;

use parallel\FutureInterface as Futures;
use Async\Spawn\FutureInterface;

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
  public function run(?\closure $task = null, ...$argv): Futures;

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
}
