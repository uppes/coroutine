<?php

declare(strict_types=1);

namespace parallel;

use Async\ParallelInterface;
use Async\Spawn\FutureInterface;
use parallel\Runtime;
use parallel\FutureInterface as Futures;
use parallel\Future\Error;
use parallel\Future\Error\Killed;
use parallel\Future\Error\Cancelled;

/**
 * A Future represents the return value or uncaught exception from a task, and exposes an API for cancellation.
 */
final class Future implements Futures
{
  /**
   * @var ParallelInterface
   */
  private $parallel = [];

  /**
   * @var FutureInterface
   */
  private $future = null;
  private $hasValue = false;
  private $hasCancelled = false;

  public function __destruct()
  {
    if ($this->future !== null && $this->parallel !== null && !$this->future->isKilled()) {
      if (!$this->hasValue) {
        $this->parallel->tick($this->future);
        $this->hasValue = true;
      }
    }

    $this->parallel = null;
  }

  /* Create */
  public function __construct($runtime = null)
  {
    if (empty($runtime) || !$runtime instanceof Runtime)
      throw new Error('construction of Future objects is not allowed');

    $this->future = $runtime->getFuture();
    $this->parallel = $runtime->getParallel();
  }

  /* Resolution */
  public function value()
  {
    if ($this->future->isKilled())
      throw new Killed('cannot retrieve value');
    elseif ($this->hasCancelled)
      throw new Cancelled('cannot retrieve value');

    if (!$this->hasValue) {
      $this->parallel->tick($this->future);
      $this->hasValue = true;
    }

    return $this->future->getResult();
  }

  /* State */
  public function cancelled(): bool
  {
    return $this->future->isTerminated() && $this->hasCancelled;
  }

  public function done(): bool
  {
    return !$this->future->isRunning() && $this->future->isStarted();
  }

  /* Cancellation */
  public function cancel(): bool
  {
    if (!$this->future->isStarted())
      $this->future->start();

    if ($this->future->isKilled())
      throw new Killed('runtime executing task was killed');
    elseif ($this->hasCancelled)
      throw new Cancelled('task was already cancelled');
    elseif (!$this->future->isRunning())
      return false;

    $this->parallel->cancel($this->future);
    $this->hasCancelled = true;
    return $this->future->isTerminated();
  }
}
