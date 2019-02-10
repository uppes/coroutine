<?php

namespace Async\Coroutine;

use Async\Coroutine\TaskInterface;

interface CoroutineInterface 
{

    public function add(\Generator $coroutine);

    public function schedule(TaskInterface $task);

    public function remove(int $tid);
	
    public function run();

    public function waitForRead($socket, TaskInterface $task);

    public function waitForWrite($socket, TaskInterface $task);
}
