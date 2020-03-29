<?php

declare(strict_types=1);

namespace Async\Coroutine;

use Async\Spawn\LauncherInterface;
use Async\Coroutine\CoroutineInterface;

/**
 * @internal
 */
final class Process
{
    private $processes = array();
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
        if ($this->isPcntl() && !\function_exists('uv_spawn'))
            $this->registerProcess();
        // @codeCoverageIgnoreEnd
    }

    public function add(LauncherInterface $process)
    {
        $this->processes[$process->getPid()] = $process;
    }

    public function remove(LauncherInterface $process)
    {
        unset($this->processes[$process->getPid()]);
    }

    public function stop(LauncherInterface $process)
    {
        $this->remove($process);
        $process->stop();
    }

    public function stopAll()
    {
        if ($this->processes) {
            foreach ($this->processes as $process) {
                $this->stop($process);
            }
        }
    }

    public function processing()
    {
        if (!empty($this->processes)) {
            foreach ($this->processes as $process) {
               if ($process->isTimedOut()) {
                    $this->remove($process);
                    $this->coroutine->executeTask($this->timedOutCallback, $process);
                    continue;
                }

                if (!$this->pcntl || \function_exists('uv_spawn')) {
                    if ($process->isRunning()) {
                        continue;
                    } elseif ($process->isSignaled()) {
                        $this->remove($process);
                        $this->coroutine->executeTask($this->signalCallback, $process);
                    } elseif ($process->isSuccessful()) {
                        $this->remove($process);
                        $this->coroutine->executeTask($this->finishCallback, $process);
                    } elseif ($process->isTerminated()) {
                        $this->remove($process);
                        $this->coroutine->executeTask($this->failCallback, $process);
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
        return empty($this->processes);
    }

    public function count(): int
    {
        return \count($this->processes);
    }

    public function isPcntl(): bool
    {
        $this->pcntl = $this->coroutine->isPcntl();

        return $this->pcntl;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function registerProcess()
    {
        \pcntl_async_signals(true);

        \pcntl_signal(\SIGCHLD, function ($signo, $status) {
            while (true) {
                $pid = \pcntl_waitpid(-1, $processState, \WNOHANG | \WUNTRACED);

                if ($pid <= 0) {
                    break;
                }

                $process = $this->processes[$pid] ?? null;

                if (!$process) {
                    continue;
                }

                if ($process instanceof LauncherInterface && $process->isSignaled()) {
                    $this->remove($process);
                    $this->coroutine->executeTask($this->signalCallback, $process);
                    continue;
                }

                if ($status['status'] === 0) {
                    $this->remove($process);
                    $this->coroutine->executeTask($this->finishCallback, $process);

                    continue;
                }

                $this->remove($process);
                $this->coroutine->executeTask($this->failCallback, $process);
            }
        });
    }
}
