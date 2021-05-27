<?php

declare(strict_types=1);

namespace parallel;

use Async\ParallelInterface;
use Async\Spawn\Channeled;
use parallel\Future;
use parallel\FutureInterface as Futures;
use parallel\RuntimeInterface;
use Async\Spawn\FutureInterface;
use parallel\Runtime\Error\Bootstrap;
use parallel\Runtime\Error\Closed;
use parallel\Runtime\Error\IllegalFunction;
use parallel\Runtime\Error\IllegalInstruction;
use parallel\Runtime\Error\IllegalParameter;
use parallel\Runtime\Error\IllegalReturn;
use parallel\Runtime\Error\IllegalVariable;

/**
 * Each runtime represents a single PHP **process**, _the thread_, a process is created (and bootstrapped) upon construction.
 *
 * The *thread* then waits for tasks to be scheduled: Scheduled tasks will be executed FIFO and then the thread will
 * resume waiting until more tasks are scheduled, or it's closed, killed, or destroyed by the normal scoping rules
 * of PHP objects.
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

  private $open = true;

  /* Create */
  public function __construct(?string $file = null)
  {
    $this->include = $file;
    $this->parallel = \coroutine_create()->getParallel();
  }

  /* Execute */
  public function run(?\closure $task = null, ...$argv): Futures
  {
    if ($this->isClosed())
      throw new Closed('already closed');

    $file = $this->include;
    $this->future = $that = $this->parallel->adding($task, $file, ...$argv);

    $this->future->signal(\SIGKILL, function () use ($that) {
      $that->close();
    });

    $this->future->catch(function (\Throwable $error) {
      $message = $error->getMessage();
      if (\strpos($message, 'open stream: No such file or directory') !== false)
        throw new Bootstrap($message);
      elseif (\strpos($message, 'is not allowed') !== false || \strpos($message, 'syntax error, unexpected') !== false)
        throw new IllegalInstruction($message);
      elseif (\strpos($message, 'Undefined variable') !== false || \strpos($message, 'Return value of') !== false)
        throw new IllegalReturn('return of task ignored by caller, caller must retain reference to Future');
      elseif (\strpos($message, 'Call to a member function') !== false)
        throw new IllegalFunction("illegal function type (internal)");
      elseif (\strpos($message, 'Serialization of') !== false)
        throw new IllegalVariable("illegal variable in static scope of function");
      elseif (\strpos($message, 'Too few arguments to function') !== false || \strpos($message, 'must be an instance of') !== false)
        throw new IllegalParameter("illegal parameter");
      else
        throw $error;
    });

    $channel = $this->future->getChannel();
    $future = new Future($this);
    if ($channel instanceof Channeled)
      $channel->setParalleling($future);

    return $future;
  }

  protected function isClosed(): bool
  {
    return !$this->open;
  }

  /* Join */
  public function close(): void
  {
    if ($this->isClosed())
      throw new Closed('already closed');

    $this->future->close();
    $this->open = false;
  }

  public function kill(): void
  {
    $this->open = false;
    $this->parallel->kill();
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
