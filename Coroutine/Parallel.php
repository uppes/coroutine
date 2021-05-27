<?php

declare(strict_types=1);

namespace Async;

use ArrayAccess;
use InvalidArgumentException;
use Async\ParallelStatus;
use Async\ParallelInterface;
use Async\CoroutineInterface;
use Async\Spawn\FutureInterface;
use Async\Spawn\ChanneledInterface;
use Async\Spawn\Spawn;
use parallel\Channel;
use parallel\RuntimeInterface;

/**
 * @internal
 */
final class Parallel implements ArrayAccess, ParallelInterface
{
  private $coroutine = null;
  private $futures = null;
  private $status;
  private $future;
  private $concurrency = 100;
  private $queue = [];
  private $results = [];
  private $finished = [];
  private $failed = [];
  private $timeouts = [];
  private $signaled = [];
  private $parallel = [];
  private $isKilling = false;

  public function __destruct()
  {
    $this->close();
  }

  public function __construct(CoroutineInterface $coroutine)
  {
    $this->coroutine = $coroutine;

    $this->futures = $this->coroutine->getFutureHandler(
      [$this, 'markAsTimedOut'],
      [$this, 'markAsFinished'],
      [$this, 'markAsFailed'],
      [$this, 'markAsSignaled']
    );

    $this->status = new ParallelStatus($this);
  }

  public function kill()
  {
    $this->isKilling = true;
    $this->close();
  }

  public function close()
  {
    if (!empty($this->parallel)) {
      foreach ($this->parallel as $future) {
        if ($future instanceof FutureInterface) {
          if ($this->isKilling)
            $future->Kill();

          $future->close();
        }
      }
    }

    $this->coroutine = null;
    $this->futures = null;
    $this->status = null;
    $this->future = null;
    $this->concurrency = 100;
    $this->queue = [];
    $this->results = [];
    $this->finished = [];
    $this->failed = [];
    $this->timeouts = [];
    $this->signaled = [];
    $this->parallel = [];
  }

  public function concurrency(int $concurrency): ParallelInterface
  {
    $this->concurrency = $concurrency;

    return $this;
  }

  public function sleepTime(int $sleepTime)
  {
    $this->futures->sleepTime($sleepTime);
  }

  public function results(): array
  {
    return $this->results;
  }

  public function isPcntl(): bool
  {
    return $this->futures->isPcntl();
  }

  public function status(): ParallelStatus
  {
    return $this->status;
  }

  public function adding(?\closure $future = null, ?string $include = null, ...$args): FutureInterface
  {
    global $___paralleling, $___bootstrap___, $___run___;

    if (!is_callable($future) && !$future instanceof FutureInterface) {
      throw new InvalidArgumentException('The future passed to Parallel::adding should be callable.');
    }

    if (!$future instanceof FutureInterface) {
      $channel = null;
      foreach ($args as $isChannel) {
        if ($isChannel instanceof ChanneledInterface) {
          $channel = $isChannel;
          break;
        } elseif (\is_string($isChannel) && Channel::isChannel($isChannel)) {
          $channel = Channel::open($isChannel);
          break;
        }
      }

      $transfer = [];
      if ($___run___ instanceof RuntimeInterface)
        $transfer['___run___'] = '___paralleled';

      if (!empty($___bootstrap___) && \is_string($___bootstrap___))
        $transfer['___bootstrap___'] = $___bootstrap___;

      // `ext-parallel` is internally passing globals around between threads, this mimics some of it's behavior.
      $transfer = \is_array($___paralleling) ? \array_merge($___paralleling, $transfer) : $transfer;

      // @codeCoverageIgnoreStart
      $executable = function () use ($future, $args, $include, $transfer) {
        \paralleling_setup($include, $transfer);
        return $future(...$args);
      };
      // @codeCoverageIgnoreEnd

      $future = Spawn::create($executable, 0, $channel, true)->displayOn();
      if ($channel !== null)
        $future->setChannel($channel);
    }

    $this->putInQueue($future, false, true);

    $this->parallel[] = $this->future = $future;

    return $future;
  }

  public function add($future, int $timeout = 0, $channel = null): FutureInterface
  {
    if (!is_callable($future) && !$future instanceof FutureInterface) {
      throw new InvalidArgumentException('The future passed to Parallel::add should be callable.');
    }

    if (!$future instanceof FutureInterface) {
      $future = Spawn::create($future, $timeout, $channel, true);
    }

    $this->putInQueue($future);

    $this->parallel[] = $this->future = $future;

    return $future;
  }

  private function notify($restart = false, $isAdding = false)
  {
    if ($this->futures->count() >= $this->concurrency) {
      return;
    }

    $future = \array_shift($this->queue);

    if (!$future) {
      return;
    }

    $this->putInProgress($future, $restart, $isAdding);
  }

  public function retry(FutureInterface $future = null, $isAdding = false): FutureInterface
  {
    $this->putInQueue((empty($future) ? $this->future : $future), true, $isAdding);

    return $this->future;
  }

  public function wait(): array
  {
    while (true) {
      if (!$this->coroutine instanceof CoroutineInterface)
        break;

      $this->coroutine->run();
      if ($this->futures->isEmpty()) {
        $this->coroutine->ioStop();
        break;
      }
    }

    return $this->results;
  }

  public function cancel(FutureInterface $future): void
  {
    $future->stop();
    $future->channelTick(1);
    while ($future->isRunning())
      $future->channelTick(0);
  }

  public function tick(FutureInterface $future)
  {
    if (!$this->coroutine instanceof CoroutineInterface || $this->isKilling)
      return;

    $this->coroutine->futureOn();
    $this->coroutine->run();
    $this->coroutine->futureOff();

    while ($future !== null && $future->isRunning()) {
      if ($future->isKilled())
        break;

      $this->coroutine->run();
    }
  }

  /**
   * @return FutureInterface[]
   */
  public function getQueue(): array
  {
    return $this->queue;
  }

  private function putInQueue(FutureInterface $future, $restart = false, $isAdding = false)
  {
    $this->queue[$future->getId()] = $future;

    $this->notify($restart, $isAdding);
  }

  private function putInProgress(FutureInterface $future, $restart = false, $isAdding = false)
  {
    unset($this->queue[$future->getId()]);

    if ($restart) {
      $future = $future->restart();
      $this->future = $future;
    } else {
      if (!$isAdding) {
        if (!\IS_PHP8)
          $future->start();
        elseif (!$future->isRunning())
          $future->start();
      }
    }

    $this->futures->add($future);
  }

  public function markAsFinished(FutureInterface $future)
  {
    $this->notify();

    $this->results[] = yield from $future->triggerSuccess(true);

    $this->finished[$future->getPid()] = $future;
  }

  public function markAsTimedOut(FutureInterface $future)
  {
    $this->notify();

    yield $future->triggerTimeout(true);

    $this->timeouts[$future->getPid()] = $future;
  }

  public function markAsSignaled(FutureInterface $future)
  {
    $this->notify();

    yield $future->triggerSignal($future->getSignaled());

    $this->signaled[$future->getPid()] = $future;
  }

  public function markAsFailed(FutureInterface $future)
  {
    $this->notify();

    yield $future->triggerError(true);

    $this->failed[$future->getPid()] = $future;
  }

  public function getFinished(): array
  {
    return $this->finished;
  }

  public function getFailed(): array
  {
    return $this->failed;
  }

  public function getTimeouts(): array
  {
    return $this->timeouts;
  }

  public function getSignaled(): array
  {
    return $this->signaled;
  }

  public function offsetExists($offset)
  {
    return isset($this->parallel[$offset]);
  }

  public function offsetGet($offset)
  {
    return isset($this->parallel[$offset]) ? $this->parallel[$offset] : null;
  }

  public function offsetSet($offset, $value, int $timeout = 0)
  {
    $this->add($value, $timeout);
  }

  public function offsetUnset($offset)
  {
    $this->futures->remove($this->parallel[$offset]);
    unset($this->parallel[$offset]);
  }
}
