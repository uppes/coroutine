<?php

declare(strict_types=1);

namespace parallel;

use Async\ParallelInterface;
use parallel\FutureInterface as Futures;
use parallel\Future\Error;
use Async\Spawn\FutureInterface;

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

  public function __destruct()
  {
    if ($this->future !== null && $this->parallel !== null)
      $this->parallel->wait();

    $this->future = null;
    $this->parallel = null;
  }

  /* Create */
  public function __construct($runtime = null)
  {
    if (empty($runtime) || !$runtime instanceof RuntimeInterface)
      throw new Error('construction of Future objects is not allowed');

    $this->future = $runtime->getFuture();
    $this->parallel = $runtime->getParallel();
  }

  /* Resolution */
  public function value()
  {
    if (!$this->hasValue || !$this->done()) {
      $this->parallel->wait();
      $this->hasValue = true;
    }

    return $this->future->getResult();
  }

  /* State */
  public function cancelled(): bool
  {
    return $this->future->isTerminated() && !$this->future->isSuccessful();
  }

  public function done(): bool
  {
    return (!$this->future->isRunning() && $this->future->isStarted())
      && ($this->future->isTerminated() || $this->future->isSuccessful());
  }

  /* Cancellation */
  public function cancel(): bool
  {
    return $this->future->stop()->isTerminated();
  }
}
