<?php

namespace parallel;

interface InputInterface
{
  /**
   * Shall set input for the given target
   *
   * @param string $target
   * @param mixed $value
   * @return void
   */
  public function add(string $target, $value): void;

  /**
   * Shall remove input for the given target
   *
   * @param string $target
   * @return void
   */
  public function remove(string $target): void;

  /**
   * Shall remove input for all targets
   *
   * @return void
   */
  public function clear(): void;
}
