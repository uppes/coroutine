<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;

use Async\Coroutine\Tasks\AllTask;
use Async\Coroutine\Tasks\MultipleFailureException;

use Async\Tests\Tasks\TaskStub;
use PHPUnit\Framework\TestCase;

/**
 * NOTE that AllTask is a subclass of SomeTask - only AllTask specific functionality
 * is tested here (i.e. the requirement of all tasks completing successfully)
 * 
 * More stringent tests of SomeTask can be found in {@see \Async\Tests\Task\SomeTaskTest}
 */
class AllTaskTest extends TestCase 
{
    /**
     * Tests that an AllTask completes successfully only after all subtasks have
     * completed
     */
    public function testCompletesWhenAllSubTasksComplete() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        $task = new AllTask($tasks);
        
        // Complete each subtask in turn, checking that the all task does not
        // complete too early
        $i = 0;
        foreach( $tasks as $subTask ) {
            $i++;
            $this->assertFalse($task->isComplete());
            $subTask->setResult($i);
            $task->tick($this->getMockBuilder(SchedulerInterface::class)->getMock());
        }
        
        // Check that the all task is now complete with the correct result        
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals([1, 2, 3, 4], $task->getResult());
    }
    
    /**
     * Tests that an AllTask fails when a single subtask fails
     */
    public function testFailsWhenOneSubTaskFails() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        $task = new AllTask($tasks);
        
        // Complete a subtask, checking that the task is not yet complete
        $this->assertFalse($task->isComplete());
        
        $tasks[0]->setResult(10);
        $task->tick($this->getMockBuilder(SchedulerInterface::class)->getMock());
        $this->assertFalse($task->isComplete());
        
        // Fail a single task and check that the task is now failed
        $tasks[1]->setException(new \Exception("failure"));
        $task->tick($this->getMockBuilder(SchedulerInterface::class)->getMock());
        
        // Check that the task is now complete with an error
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        $this->assertInstanceOf(
            MultipleFailureException::class, $task->getException()
        );
        $this->assertContains('1 tasks failed', $task->getException()->getMessage());
        $this->assertEquals(
            [1 => new \Exception("failure")],
            $task->getException()->getFailures()
        );
    }
}
