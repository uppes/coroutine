<?php

declare(strict_types=1);

namespace parallel;

use Async\ParallelInterface;
use parallel\FutureInterface;
use Async\Spawn\LauncherInterface;

final class Future implements FutureInterface
{
  /**
   * @var ParallelInterface
   */
  private $parallel = [];

  /**
   * @var LauncherInterface
   */
  private $future = null;

  public function __destruct()
  {
    $this->parallel->wait();
    $this->parallel = null;
  }

  /* Create */
  public function __construct(RuntimeInterface $runtime)
  {
    $this->future = $runtime->getFuture();
    $this->parallel = $runtime->getParallel();
  }

  /* Resolution */
  public function value()
  {
    $this->parallel->wait();
    return \deserialize($this->future->getResult());
  }

  /* State */
  public function cancelled(): bool
  {
    return $this->future->isTerminated() && !$this->future->isSuccessful();
  }

  public function done(): bool
  {
    return !$this->future->isRunning();
  }

  /* Cancellation */
  public function cancel(): bool
  {
    return $this->future->stop()->isTerminated();
  }
}
