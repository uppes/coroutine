<?php

namespace Async\Tests\Scheduler;

use Async\Coroutine\Scheduler;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Tests\Scheduler\AbstractSchedulerTest;

class SchedulerTest extends AbstractSchedulerTest 
{
	use \Async\Tests\getMocker;
	
	protected function setUp(): void
    {
        $this->scheduler = new Scheduler();
    }

    /**
     * Test that attempting to schedule a task with a delay results in an error
     */
    public function testScheduleWithDelayIsError()
	{
        $this->expectException(\RuntimeException::class);
        // Try to schedule a task with a delay
        $this->scheduler->schedule($this->getMock(TaskInterface::class), 0.05);
    }
    
    /**
     * Test that attempting to schedule a task with a tickInterval results in an error
     */
    public function testScheduleWithTickIntervalIsError() 
	{
        $this->expectException(\RuntimeException::class);
        // Try to schedule a task with a delay
        $this->scheduler->schedule($this->getMock(TaskInterface::class), null, 0.05);
    }
}
