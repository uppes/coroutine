<?php

declare(strict_types = 1);

namespace Async\Coroutine;

use Async\Processor\ProcessInterface;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\CoroutineInterface;

/**
 * @internal 
 */
class Process
{
    private $processes = array();
    private $sleepTime = 15000;
    private $timedOutCallback = null;
    private $finishCallback = null;
    private $failCallback = null;
    private $pcntl = false;
    private $coroutine = null;
	
    public function __construct(CoroutineInterface $coroutine = null, 
        $timedOutCallback = null, $finishCallback = null, $failCallback = null)
    {
        $this->coroutine = empty($coroutine) ? \coroutine_instance() : $coroutine;
        $this->init($timedOutCallback,  $finishCallback,  $failCallback);
		
		if ($this->isPcntl())
            $this->registerProcess();
    }

    public function add(ProcessInterface $process)
    {
        $this->processes[$process->getPid()] = $process;		
    }

    public function remove(ProcessInterface $process)
    {
        unset($this->processes[$process->getPid()]);
    }

    public function stop(ProcessInterface $process)
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
        if (! empty($this->processes)) {
            foreach ($this->processes as $process) {
                if ($process->isTimedOut()) {
                    $this->remove($process);
					$markTimedOuted = $this->timedOutCallback;

                    if ($markTimedOuted($process) instanceof \Generator)
                        $this->coroutine->createTask($markTimedOuted($process));
                    elseif ($markTimedOuted instanceof TaskInterface)
                        $this->coroutine->schedule($markTimedOuted);
                } 
                
                if (! $this->pcntl) {
					if ($process->isRunning()) {
                        continue;
					} elseif ($process->isSuccessful()) {
                        $this->remove($process);
						$markFinished = $this->finishCallback;

                        if ($markFinished($process) instanceof \Generator)
                            $this->coroutine->createTask($markFinished($process));
                        elseif ($markFinished instanceof TaskInterface)
                            $this->coroutine->schedule($markFinished);
                    } elseif ($process->isTerminated()) {
                        $this->remove($process);
                        $markFailed = $this->failCallback;

                        if ($markFailed($process) instanceof \Generator)
                            $this->coroutine->createTask($markFailed($process));
                        elseif ($markFailed instanceof TaskInterface)
                            $this->coroutine->schedule($markFailed);
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
	
    public function init($timedOutCallback = null, $finishCallback = null, $failCallback = null)
    {
        $this->timedOutCallback = $timedOutCallback;
        $this->finishCallback = $finishCallback;
        $this->failCallback = $failCallback;
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

                if (! $process) {
                    continue;
                }

                if ($status['status'] === 0) {
                    $this->remove($process);
                    $markFinished = $this->finishCallback;

                    if ($markFinished($process) instanceof \Generator)
                        $this->coroutine->createTask($markFinished($process));
                    elseif ($markFinished instanceof TaskInterface)
                        $this->coroutine->schedule($markFinished);

                    continue;
                }
				
                $this->remove($process);
                $markFailed = $this->failCallback;

                if ($markFailed($process) instanceof \Generator)
                    $this->coroutine->createTask($markFailed($process));
                elseif ($markFailed instanceof TaskInterface)
                    $this->coroutine->schedule($markFailed);
            }
        });
    }
}
