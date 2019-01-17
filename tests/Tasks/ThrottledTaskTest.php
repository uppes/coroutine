<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Coroutine\Tasks\ThrottledTask;
use Async\Tests\Tasks\TaskStub;
use PHPUnit\Framework\TestCase;

class ThrottledTaskTest extends TestCase 
{
	public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false, $callOriginalMethods = false, $proxyTarget = null)
	{
		$builder = $this->getMockBuilder($originalClassName);

		if (is_array($methods)) {
			$builder->setMethods($methods);
		}

		if (is_array($arguments)) {
			$builder->setConstructorArgs($arguments);
		}

		$callOriginalConstructor ? $builder->enableOriginalConstructor() : $builder->disableOriginalConstructor();
		$callOriginalClone ? $builder->enableOriginalClone() : $builder->disableOriginalClone();
		$callAutoload ? $builder->enableAutoload() : $builder->disableAutoload();
		$cloneArguments ? $builder->enableOriginalClone() : $builder->disableOriginalClone();
		$callOriginalMethods ? $builder->enableProxyingToOriginalMethods() : $builder->disableProxyingToOriginalMethods();

		if ($mockClassName) {
			$builder->setMockClassName($mockClassName);
		}

		if ($proxyTarget) {
			$builder->setProxyTarget($proxyTarget);
		}

		$mockObject = $builder->getMock();

		return $mockObject;
	}
	
    /**
     * Test that the wrapped task is scheduled with the correct tick interval on
     * the first tick of a ThrottledTask
     */
    public function testWrappedTaskScheduledWithTickIntervalOnFirstTick() 
	{
        $wrapped = $this->getMock(TaskInterface::class);
        $interval = 0.123;
		
        $throttled = new ThrottledTask($wrapped, $interval);
        
        // Create a mock scheduler that expects to be called once with the wrapped
        // task and delay
        $scheduler = $this->getMock(SchedulerInterface::class);
        $scheduler->expects($this->once())->method('schedule')->with($wrapped, null, $interval);
        
        // Tick the delayed task twice with the scheduler
        $throttled->tick($scheduler);
        $throttled->tick($scheduler);
    }
    
    /**
     * Test that the wrapped task is cancelled when the throttled task is cancelled
     */
    public function testWrappedTaskCancelledWhenThrottledTaskCancelled() 
	{
        // Create a wrapped task that expects cancel to be called once
        $wrapped = $this->getMock(TaskInterface::class);
        $wrapped->expects($this->once())->method('cancel');
        
        $throttled = new ThrottledTask($wrapped, 0.5);
        
        $throttled->cancel();
    }
    
    /**
     * Test that the throttled task takes on the result of the wrapped task when
     * the wrapped task completes
     */
    public function testThrottledTaskCompletesWithResultWhenWrappedTaskCompletesWithResult() 
	{
        $wrapped = new TaskStub();
        
        $throttled = new ThrottledTask($wrapped, 0.5);
        
        // Check that the task is not complete
        $this->assertFalse($throttled->isComplete());
        
        // Complete the wrapped task and check that the the throttled task reflects
        // that
        $wrapped->setResult("Some result");
        $this->assertTrue($throttled->isComplete());
        $this->assertTrue($throttled->isSuccessful());
        $this->assertSame("Some result", $throttled->getResult());
    }
    
    /**
     * Test that the throttled task takes on the error of the wrapped task when
     * the wrapped task completes with an error
     */
    public function testThrottledTaskCompletesWithErrorWhenWrappedTaskCompletesWithError() 
	{
        $wrapped = new TaskStub();
        
        $throttled = new ThrottledTask($wrapped, 0.5);
        
        // Check that the task is not complete
        $this->assertFalse($throttled->isComplete());
        
        // Complete the wrapped task with an error and check that the the throttled task reflects
        // that
        $wrapped->setException(new \Exception("Some exception"));
        $this->assertTrue($throttled->isComplete());
        $this->assertTrue($throttled->isFaulted());
        $this->assertSame("Some exception", $throttled->getException()->getMessage());
    }
    
    /**
     * Test that the throttled task is cancelled when the wrapped task is cancelled
     */
    public function testThrottledTaskCancelledWhenWrappedTaskCancelled() 
	{
        $wrapped = new TaskStub();
        
        $throttled = new ThrottledTask($wrapped, 0.5);
        
        // Check that the task is not complete
        $this->assertFalse($throttled->isComplete());
        
        // Cancel the wrapped task and check that the the throttled task is cancelled
        $wrapped->cancel();
        $this->assertTrue($throttled->isComplete());
        $this->assertTrue($throttled->isCancelled());
    }
}
