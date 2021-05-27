<?php

declare(strict_types=1);

namespace parallel;

use Async\Spawn\Channeled;
use parallel\Channel\Error\Closed;
use parallel\Channel\Error\Existence;
use parallel\Channel\Error\IllegalValue;

/**
 * A task may be scheduled with arguments, use lexical scope variables, and return a value,
 * but these only allow uni-directional communication:
 * - They allow the programmer to send data into and retrieve data from
 * a task, but do not allow bi-directional communication between tasks.
 *
 * The **Channel** API allows bi-directional communication between tasks, a socket-like link between tasks
 * that the programmer can use to send and receive data.
 */
final class Channel extends Channeled
{
  public static function throwExistence(string $errorMessage): void
  {
    throw new Existence($errorMessage);
  }

  public static function throwClosed(string $errorMessage): void
  {
    throw new Closed($errorMessage);
  }

  /**
   * @codeCoverageIgnore
   */
  public static function throwIllegalValue(string $errorMessage): void
  {
    throw new IllegalValue($errorMessage);
  }
}
