<?php

namespace Async\Tests\Scheduler;

use Async\Coroutine\Scheduler;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Tests\Scheduler\AbstractSchedulerTest;

class SchedulerTest extends AbstractSchedulerTest 
{
	use \Async\Tests\getMocker;
	
	protected function setUp()
    {
        $this->scheduler = new Scheduler();
    }

    /**
     * Test that attempting to schedule a task with a delay results in an error
     * 
     * @expectedException \RuntimeException
     */
    public function testScheduleWithDelayIsError()
	{
        // Try to schedule a task with a delay
        $this->scheduler->schedule($this->getMock(TaskInterface::class), 0.05);
    }
    
    /**
     * Test that attempting to schedule a task with a tickInterval results in an error
     * 
     * @expectedException \RuntimeException
     */
    public function testScheduleWithTickIntervalIsError() 
	{
        // Try to schedule a task with a delay
        $this->scheduler->schedule($this->getMock(TaskInterface::class), null, 0.05);
    }
}