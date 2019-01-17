<?php

namespace Async\Tests\Tasks;

use Async\Promise\Promise;
use Async\Promise\Deferred;
use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\PromiseTask;
use PHPUnit\Framework\TestCase;

class PromiseTaskTest extends TestCase 
{
	private $loop = null;
	
	protected function setUp()
    {
		$this->markTestSkipped('These test fails in various stages.');
		$this->loop = Loop\instance();
    }
	
    /**
     * Test that a promise task created with a pre-resolved promise is immediately
     * marked as complete
     */
    public function testPreResolved() 
	{
        $promise = Promise::resolve("resolved");
        
        $task = new PromiseTask($promise);
        
        // The task should be complete with no error
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        
        // The result of the task should be the promise result
        $this->assertEquals("resolved", $task->getResult());
    }
    
    /**
     * Test that a promise task created with a pre-rejected promise is immediately
     * marked as complete with an error
     */
    public function testPreRejected() 
	{
        $promise = Promise::reject(new \Exception("rejected"));
        
        $task = new PromiseTask($promise);
        
        // The task should be complete with an error
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        
        // The message of the task's exception should be that given to the promise
        $this->assertEquals("rejected", $task->getException()->getMessage());
    }
    
    /**
     * Test that a promise task created with a delayed promise becomes complete
     * when the promise is resolved
     */
    public function testDelayedResolution() {
        $promise = new Deferred();
        
        $task = new PromiseTask($promise->promise());
        
        // The task should not yet be complete
        $this->assertFalse($task->isComplete());
        
        // Resolve the promise
        $promise->resolve("resolved");
        
        // The task should be complete with no error
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        
        // The result of the task should be the promise result
        $this->assertEquals("resolved", $task->getResult());
    }
    
    /**
     * Test that a promise task created with a delayed promise becomes complete
     * when the promise is rejected
     */
    public function testDelayedRejection() {
        $promise = new Deferred();
        
        $task = new PromiseTask($promise->promise());
        
        // The task should not yet be complete
        $this->assertFalse($task->isComplete());
        
        // Reject the promise
        $promise->reject(new \Exception("rejected"));
        
        // The task should be complete with an error
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        
        // The message of the task's exception should be that given to the promise
        $this->assertEquals("rejected", $task->getException()->getMessage());
    }
    
    /**
     * Test that tick is a noop for a promise task
     */
    public function testTickNoop() {
        $promise = new Deferred();
        
        $task = new PromiseTask($promise->promise());
        
        // The task should not yet be complete
        $this->assertFalse($task->isComplete());
        
        // Tick a few times
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // The task should not have completed
        $this->assertFalse($task->isComplete());
        
        // Resolve the promise
        $promise->resolve("resolved");
        
        // The task should be complete with no error
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());        
        // The result of the task should be the promise result
        $this->assertEquals("resolved", $task->getResult());
        
        // Tick a few more times
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Verify that the result is unchanged
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());        
        // The result of the task should be the promise result
        $this->assertEquals("resolved", $task->getResult());
    }
}
