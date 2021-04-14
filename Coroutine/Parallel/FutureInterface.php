<?php

namespace Async\Parallel;

interface FutureInterface
{

  /**
   * Shall return (and if necessary wait for) return from task
   *
   * @return mixed
   */
  public function value();

  /**
   * Shall indicate if the task was cancelled
   *
   * @return boolean
   */
  public function cancelled(): bool;

  /**
   * Shall indicate if the task is completed
   *
   * @return boolean
   */
  public function done(): bool;

  /**
   * Shall try to cancel the task
   *
   * @return boolean
   */
  public function cancel(): bool;
}
