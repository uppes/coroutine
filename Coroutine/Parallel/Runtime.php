<?php

declare(strict_types=1);

namespace parallel;

use Async\ParallelInterface;
use parallel\Future;
use parallel\FutureInterface as Futures;
use parallel\RuntimeInterface;
use Async\Spawn\FutureInterface;

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
   * @var FutureInterface
   */
  private $future = null;

  private $include = null;

  /* Create */
  public function __construct(?string $file = null)
  {
    $this->include = $file;
    $this->parallel = \coroutine_create()->getParallel();
  }

  /* Execute */
  public function run(?\closure $task = null, ...$argv): Futures
  {
    $file = $this->include;
    $this->future = $this->parallel->add(
      function () use ($task, $argv, $file) {
        if (!empty($file) && \is_string($file))
          include $file;

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
    $this->include = null;
  }

  public function kill(): void
  {
    \coroutine_instance()->getProcess();
  }

  public function getFuture(): FutureInterface
  {
    return $this->future;
  }

  public function getParallel(): ParallelInterface
  {
    return $this->parallel;
  }
}
