<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;

class TaskStub extends AbstractTask 
{
    public function setResult($result) 
	{
        $this->result = $result;
        $this->successful = true;
    }

    public function setException(\Exception $exception) 
	{
        $this->exception = $exception;
    }

    protected function doTick(SchedulerInterface $scheduler) { /* NOOP */ }
}
