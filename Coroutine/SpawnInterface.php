<?php

namespace Async\Coroutine;

use Async\Coroutine\SpawnInterface;
use Async\Processor\ProcessInterface;

interface SpawnInterface 
{
    /**
     * @return static
     */
    public static function create(): SpawnInterface;
	
    public function concurrency(int $concurrency): SpawnInterface;

    public function sleepTime(int $sleepTime);
    
    public function results(): array;

    public function isPcntl(): bool;

    public function status(): SpawnStatus;
	
    /**
     * @param ProcessInterface|callable $process
     * @param int $timeout
     * @return ProcessInterface
     */	
    public function add($process, int $timeout = 300): ProcessInterface;

    public function retry(ProcessInterface $process = null): ProcessInterface;

    public function wait(): array;
	
    /**
     * @return ProcessInterface[]
     */
    public function getQueue(): array;
	
    public function markAsFinished(ProcessInterface $process);

    public function markAsTimedOut(ProcessInterface $process);

    public function markAsFailed(ProcessInterface $process);
	
    public function getFinished(): array;

    public function getFailed(): array;

    public function getTimeouts(): array;
}
