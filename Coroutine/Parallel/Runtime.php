<?php

declare(strict_types=1);

namespace parallel;

use Async\ParallelInterface;
use Async\Spawn\LauncherInterface;
use parallel\RuntimeInterface;
use parallel\FutureInterface;

/**
 * @internal
 */
final class Runtime implements RuntimeInterface
{
  /**
   * @var ParallelInterface
   */
  private $parallel = [];

  /**
   * @var LauncherInterface
   */
  private $future = null;

  /* Create */
  public function __construct()
  {
    $this->parallel = \coroutine_create()->getParallel();
  }

  /* Execute */
  public function run(?\closure $task = null, ...$argv): FutureInterface
  {

    $this->future = $this->parallel->add(
      function () use ($task, $argv) {
        return \flush_value($task(...$argv), 50);
      }
    )->displayOn();

    return new Future($this);
  }

  /* Join */
  public function close(): void
  {
    $this->future->close();
    $this->future = null;
    $this->parallel = null;
  }

  public function kill(): void
  {
    \coroutine_instance()->getProcess();
  }

  public function getFuture(): LauncherInterface
  {
    return $this->future;
  }

  public function getParallel(): ParallelInterface
  {
    return $this->parallel;
  }
}
