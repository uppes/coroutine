<?php

declare(strict_types=1);

namespace Async;

use Async\Spawn\FutureInterface;
use Async\CoroutineInterface;

/**
 * @internal
 */
final class FutureHandler
{
    private $futures = array();
    private $sleepTime = 15000;
    private $signalCallback = null;
    private $timedOutCallback = null;
    private $finishCallback = null;
    private $failCallback = null;
    private $pcntl = false;
    private $coroutine = null;

    public function __construct(
        CoroutineInterface $coroutine,
        $timedOutCallback = null,
        $finishCallback = null,
        $failCallback = null,
        $signalCallback = null
    ) {
        $this->coroutine = $coroutine;
        $this->init($timedOutCallback, $finishCallback, $failCallback, $signalCallback);

        // @codeCoverageIgnoreStart
        if ($this->isPcntl())
            $this->registerFutureHandler();
        // @codeCoverageIgnoreEnd
    }

    public function add(FutureInterface $future)
    {
        $this->futures[$future->getPid()] = $future;
    }

    public function remove(FutureInterface $future)
    {
        unset($this->futures[$future->getPid()]);
    }

    public function stop(FutureInterface $future)
    {
        $this->remove($future);
        $future->stop();
        $future->close();
    }

    public function stopAll()
    {
        if ($this->futures) {
            foreach ($this->futures as $future) {
                $this->stop($future);
            }
        }
    }

    public function processing()
    {
        if (!empty($this->futures)) {
            foreach ($this->futures as $future) {
                if ($future->isTimedOut()) {
                    $this->remove($future);
                    $this->coroutine->executeTask($this->timedOutCallback, $future);
                    continue;
                }

                if (!$this->pcntl) {
                    if ($future->isRunning()) {
                        continue;
                    } elseif ($future->isSignaled()) {
                        $this->remove($future);
                        $this->coroutine->executeTask($this->signalCallback, $future);
                    } elseif ($future->isSuccessful()) {
                        $this->remove($future);
                        $this->coroutine->executeTask($this->finishCallback, $future);
                    } elseif ($future->isTerminated()) {
                        $this->remove($future);
                        $this->coroutine->executeTask($this->failCallback, $future);
                    }
                }
            }
        }
    }

    public function sleepTime(int $sleepTime)
    {
        $this->sleepTime = $sleepTime;
    }

    public function sleepingTime(): int
    {
        return $this->sleepTime;
    }

    public function init($timedOutCallback = null, $finishCallback = null, $failCallback = null, $signalCallback = null)
    {
        $this->timedOutCallback = $timedOutCallback;
        $this->finishCallback = $finishCallback;
        $this->failCallback = $failCallback;
        $this->signalCallback = $signalCallback;
    }

    public function isEmpty(): bool
    {
        return empty($this->futures);
    }

    public function count(): int
    {
        return \count($this->futures);
    }

    public function isPcntl(): bool
    {
        $this->pcntl = $this->coroutine->isPcntl() && !\IS_PHP8  && !\function_exists('uv_spawn');

        return $this->pcntl;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function registerFutureHandler()
    {
        \pcntl_async_signals(true);

        \pcntl_signal(\SIGCHLD, function ($signo, $status) {
            while (true) {
                $pid = \pcntl_waitpid(-1, $futureState, \WNOHANG | \WUNTRACED);

                if ($pid <= 0) {
                    break;
                }

                $future = $this->futures[$pid] ?? null;

                if (!$future) {
                    continue;
                }

                if ($future instanceof FutureInterface && $future->isSignaled()) {
                    $this->remove($future);
                    $this->coroutine->executeTask($this->signalCallback, $future);
                    continue;
                }

                if ($status['status'] === 0) {
                    $this->remove($future);
                    $this->coroutine->executeTask($this->finishCallback, $future);

                    continue;
                }

                $this->remove($future);
                $this->coroutine->executeTask($this->failCallback, $future);
            }
        });
    }
}
