<?php

namespace Async\Coroutine;

use Async\Processor\ProcessInterface;
use Async\Coroutine\CoroutineInterface;

/**
 * @internal 
 */
class Process
{
    private $processes = array();
    private $sleepTime = 30000;
    private $timedOutCallback = null;
    private $finishCallback = null;
    private $failCallback = null;
    private $pcntl = false;
    private $coroutine = null;
	
    public function __construct(CoroutineInterface $coroutine = null,
        callable $timedOutCallback = null, 
        callable $finishCallback = null, 
        callable $failCallback = null)
    {
        $this->coroutine = empty($coroutine) ? \coroutineInstance() : $coroutine;
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

                    if (! method_exists($markTimedOuted, 'callTimeout'))
                        $this->coroutine->addTask($markTimedOuted($process));
                    else
                        $markTimedOuted($process);
                } 
                
                if (! $this->pcntl) {
					if ($process->isRunning()) {
                        continue;
					} elseif ($process->isSuccessful()) {
                        $this->remove($process);
						$markFinished = $this->finishCallback;

                        if (! method_exists($markFinished, 'callSuccess'))
                            $this->coroutine->addTask($markFinished($process));
                        else
                            $markFinished($process);
                    } elseif ($process->isTerminated()) {
                        $this->remove($process);
                        $markFailed = $this->failCallback;

                        if (! method_exists($markFailed, 'callError'))
                            $this->coroutine->addTask($markFailed($process));
                        else
                            $markFailed($process);
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
	
    public function init(
        callable $timedOutCallback = null, 
        callable $finishCallback = null, 
        callable $failCallback = null)
    {
        $this->timedOutCallback = empty($timedOutCallback) ? [$this, 'callTimeout'] : $timedOutCallback;
        $this->finishCallback = empty($finishCallback) ? [$this, 'callSuccess'] : $finishCallback;
        $this->failCallback = empty($failCallback) ? [$this, 'callError'] : $failCallback;
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

                    if (! method_exists($markFinished, 'callSuccess'))
                        $this->coroutine->addTask($markFinished($process));
                    else
                        $markFinished($process);

                    continue;
                }
				
                $this->remove($process);
                $markFailed = $this->failCallback;

                if (! method_exists($markFailed, 'callError'))
                    $this->coroutine->addTask($markFailed($process));
                else
                    $markFailed($process);
            }
        });
    }
	
    private function callSuccess(ProcessInterface $process)
    {
		$process->triggerSuccess();
    }

    private function callError(ProcessInterface $process)
    {
		$process->triggerError();
    }

    private function callTimeout(ProcessInterface $process)
    {
		$process->triggerTimeout();
    }
}
