<?php

namespace Async\Tests\Scheduler;

use Async\Tests\Scheduler\AddTaskTask;
use Async\Tests\Scheduler\ExitingTask;
use Async\Tests\Scheduler\TickCountingTask;
use PHPUnit\Framework\TestCase;

/**
 * Tests that all schedulers should be able to pass, regardless of whether they
 * implement timers or not
 */
abstract class AbstractSchedulerTest extends TestCase 
{
	use \Async\Tests\getMocker;
	
    /**
     * The scheduler under test
     * 
     * This should be set in sub-classes by overriding setUp
     *
     * @var \Async\Scheduler\Scheduler
     */
    protected $scheduler = null;
    
    /**
     * Test that run exits immediately when no tasks are scheduled
     */
    public function testNoTasksExitsImmediately() {
        $this->scheduler->run();
        
        $this->assertTrue(true, "We should get here as there are no tasks");
    }
    
    /**
     * Test that a scheduled task has it's tick method invoked repeatedly until it
     * is complete
     */
    public function testTaskInvokedUntilComplete() {
        // Schedule a task that is complete after it has been invoked a certain
        // number of times and check that the doTick method is invoked that many
        // times
        
        $task = $this->getMock(TickCountingTask::class, ['doTick'], [5]);
        $task->expects($this->exactly(5))->method('doTick')->with($this->scheduler);
        $this->scheduler->schedule($task);
        
        $this->scheduler->run();
        
        $this->assertTrue(
            true, "We should get to here unless complete tasks are being rescheduled"
        );
    }
    
    /**
     * Test that multiple tasks can be executed at once
     */
    public function testMultipleTasks() {
        // Schedule a task that is complete after it has been invoked twice
        // and check that the doTick method is invoked that many times

        $task = $this->getMock(TickCountingTask::class, ['doTick'], [2]);
        $task->expects($this->exactly(2))->method('doTick')->with($this->scheduler);
        $this->scheduler->schedule($task);
        
        // Schedule a second task that is complete after it has been invoked once
        // and check that the doTick method is invoked that many times
        $task = $this->getMock(TickCountingTask::class, ['doTick'], [1]);
        $task->expects($this->once())->method('doTick')->with($this->scheduler);
        $this->scheduler->schedule($task);
        
        $this->scheduler->run();
    }
    
    /**
     * Test that calling stop stops a running scheduler
     */
    public function testStop() {
        // Add a task that invokes stop on the scheduler
        // ExitingTask is never complete, it just calls stop on the scheduler
        $this->scheduler->schedule(new ExitingTask());
        
        $this->scheduler->run();
        
        // If we get to here, stop has worked successfully
        $this->assertTrue(true, "We should get here if stop is working");
    }
    
    /**
     * Test that a task added by another task is executed
     */
    public function testAddTaskFromParentTask() {
        // The task that will be added is a task that will be complete after being
        // invoked once

        $task = $this->getMock(TickCountingTask::class, ['doTick'], [1]);
        $task->expects($this->once())->method('doTick')->with($this->scheduler);
        
        // Schedule a task that will add that task to the scheduler when invoked
        $this->scheduler->schedule(new AddTaskTask($task));
        
        $this->scheduler->run();
    }
}
