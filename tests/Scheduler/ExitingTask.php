<?php

namespace Async\Tests\Scheduler;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;

class ExitingTask extends AbstractTask 
{
    protected function doTick(SchedulerInterface $scheduler) 
	{
        // Just stop the scheduler
        $scheduler->stop();
    }
}
