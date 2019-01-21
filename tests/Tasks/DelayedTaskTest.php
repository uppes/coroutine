<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Coroutine\Tasks\DelayedTask;
use Async\Tests\CallableStub;
use Async\Tests\Tasks\TaskStub;
use PHPUnit\Framework\TestCase;

class DelayedTaskTest extends TestCase 
{
	use \Async\Tests\getMocker;
	
    /**
     * Test that the wrapped task is scheduled with the correct delay on the first
     * tick of a DelayedTask
     */
    public function testWrappedTaskScheduledWithDelayOnFirstTick() {
        $wrapped = $this->getMock(TaskInterface::class);
        $delay = 0.123;
		
        $delayed = new DelayedTask($wrapped, $delay);
        
        // Create a mock scheduler that expects to be called once with the wrapped
        // task and delay
        $scheduler = $this->getMock(SchedulerInterface::class);
        $scheduler->expects($this->once())->method('schedule')->with($wrapped, $delay);
        
        // Tick the delayed task twice with the scheduler
        $delayed->tick($scheduler);
        $delayed->tick($scheduler);
    }
    
    /**
     * Test that the wrapped task is cancelled when the delayed task is cancelled
     */
    public function testWrappedTaskCancelledWhenDelayedTaskCancelled() {
        // Create a wrapped task that expects cancel to be called once
        $wrapped = $this->getMock(TaskInterface::class);
        $wrapped->expects($this->once())->method('cancel');
        
        $delayed = new DelayedTask($wrapped, 0.5);
        
        $delayed->cancel();
    }
    
    /**
     * Test that the delayed task takes on the result of the wrapped task when
     * the wrapped task completes
     */
    public function testDelayedTaskCompletesWithResultWhenWrappedTaskCompletesWithResult() {
        $wrapped = new TaskStub();
        
        $delayed = new DelayedTask($wrapped, 0.5);
        
        // Check that the task is not complete
        $this->assertFalse($delayed->isComplete());
        
        // Complete the wrapped task and check that the the delayed task reflects
        // that
        $wrapped->setResult("Some result");
        $this->assertTrue($delayed->isComplete());
        $this->assertTrue($delayed->isSuccessful());
        $this->assertSame("Some result", $delayed->getResult());
    }
    
    /**
     * Test that the delayed task takes on the error of the wrapped task when
     * the wrapped task completes with an error
     */
    public function testDelayedTaskCompletesWithErrorWhenWrappedTaskCompletesWithError() {
        $wrapped = new TaskStub();
        
        $delayed = new DelayedTask($wrapped, 0.5);
        
        // Check that the task is not complete
        $this->assertFalse($delayed->isComplete());
        
        // Complete the wrapped task with an error and check that the the delayed task reflects
        // that
        $wrapped->setException(new \Exception("Some exception"));
        $this->assertTrue($delayed->isComplete());
        $this->assertTrue($delayed->isFaulted());
        $this->assertSame("Some exception", $delayed->getException()->getMessage());
    }
    
    /**
     * Test that the delayed task is cancelled when the wrapped task is cancelled
     */
    public function testDelayedTaskCancelledWhenWrappedTaskCancelled() {
        $wrapped = new TaskStub();
        
        $delayed = new DelayedTask($wrapped, 0.5);
        
        // Check that the task is not complete
        $this->assertFalse($delayed->isComplete());
        
        // Cancel the wrapped task and check that the the delayed task is cancelled
        $wrapped->cancel();
        $this->assertTrue($delayed->isComplete());
        $this->assertTrue($delayed->isCancelled());
    }
}
