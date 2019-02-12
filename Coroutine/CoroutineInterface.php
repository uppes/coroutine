<?php

namespace Async\Coroutine;

use Async\Coroutine\TaskInterface;

interface CoroutineInterface 
{

    public function addTask(\Generator $coroutine);

    public function schedule(TaskInterface $task);

    public function removeTask(int $tid);
	
    public function run();

    public function addReadStream($stream, $task);

    public function addWriteStream($stream, $task);
}
