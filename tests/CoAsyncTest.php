<?php

namespace Async\Tests;

use Async\Coroutine\Co;
use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Tests\CallableStub;
use PHPUnit\Framework\TestCase;

class AsyncTest extends TestCase 
{	
    /**
     * Test that Co::async returns the given task when given a task
     */
    public function testWithTask() {
        $task = $this->getMockBuilder(TaskInterface::class)->getMock();
        
        // It should be the exact same task
        $this->assertSame($task, Co::async($task));
    }
    
    /**
     * Test that Co::async returns a CallableTask when given a callable
     */
    public function testWithCallable() {
        // The callable expects to be called once
        
        $callable = $this->getMockBuilder(CallableStub::class)->getMock();

        $callable->expects($this->once())->method('__invoke');
        
        $task = Co::async($callable);
        
        // Check it returned an instance of the correct class
        $this->assertInstanceOf(\Async\Coroutine\Tasks\CallableTask::class, $task);
        
        // Tick the task and verify it has completed and the callable was called
        $task->tick($this->getMockBuilder(SchedulerInterface::class)->getMock());
        $task->tick($this->getMockBuilder(SchedulerInterface::class)->getMock());

        $this->assertTrue($task->isComplete());
    }
    
    /**
     * Test that Co::async returns an appropriate CallableTask when given any
     * other object
     * 
     * I.e. one that, when ticked, the task result is the given object
     */
    public function testWithOther() {
        $task = Co::async(101);
        
        // Check it returned an instance of the correct class
        $this->assertInstanceOf(\Async\Coroutine\Tasks\CallableTask::class, $task);
        
        // Check the task is currently incomplete
        $this->assertFalse($task->isComplete());
        
        // Tick the task
        $task->tick($this->getMockBuilder(SchedulerInterface::class)->getMock());
        
        // Verify the task is complete and has the correct result
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals(101, $task->getResult());
    }
}
