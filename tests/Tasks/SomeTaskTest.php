<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\MultipleFailureException;
use Async\Coroutine\Tasks\SomeTask;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Coroutine\Tasks\ThrottledTask;
use Async\Coroutine\Tasks\TaskCancelledException;
use Async\Tests\Tasks\TaskStub;
use PHPUnit\Framework\TestCase;

class SomeTaskTest extends TestCase 
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
     * Test that subtasks are only scheduled if they are not already complete
     */
    public function testOnlyIncompleteSubTasksScheduled() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        // Ask for 2 successful completions before we get a result
        $task = new SomeTask($tasks, 2);
        
        // Mark the last task as complete already
        $tasks[3]->setResult(10);
        
        // Create a mock scheduler that expects to be called with the first 3 tasks
        // but not the last
        $scheduler = $this->getMock(SchedulerInterface::class);
        $scheduler->expects($this->exactly(3))->method('schedule');
        $scheduler->expects($this->at(0))->method('schedule')->with($tasks[0]);
        $scheduler->expects($this->at(1))->method('schedule')->with($tasks[1]);
        $scheduler->expects($this->at(2))->method('schedule')->with($tasks[2]);
        
        // Tick the task
        $task->tick($scheduler);
    }
    
    /**
     * Test that subtasks are only scheduled on the first tick
     */
    public function testSubTasksScheduledOnlyOnce() {
        $tasks = [new TaskStub(), new TaskStub()];
        
        // Ask for 2 successful completions before we get a result
        $task = new SomeTask($tasks, 1);
        
        // Create a mock scheduler that expects to be called with the first 3 tasks
        // but not the last
        $scheduler = $this->getMock(SchedulerInterface::class);
        $scheduler->expects($this->exactly(2))->method('schedule');
        $scheduler->expects($this->at(0))->method('schedule')->with($tasks[0]);
        $scheduler->expects($this->at(1))->method('schedule')->with($tasks[1]);
        
        // Tick the task - the scheduler should be called twice
        $task->tick($scheduler);
        
        // Create a second mock scheduler that never expects to be 
        $scheduler = $this->getMock(SchedulerInterface::class);
        $scheduler->expects($this->never())->method('schedule');
        
        // Tick the task - the scheduler should not be called again
        $task->tick($scheduler);
    }
    
    /**
     * Tests that a SomeTask completes successfully when enough subtasks complete
     * successfully
     */
    public function testCompletesWhenEnoughSubTasksComplete() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        // Ask for 2 successful completions before we get a result
        $task = new SomeTask($tasks, 2);
        
        // Check that the task is not complete
        $this->assertFalse($task->isComplete());
        
        // Complete the first task and tick
        $tasks[0]->setResult(10);
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete
        $this->assertFalse($task->isComplete());
        
        // Complete another task and tick
        $tasks[2]->setResult(12);
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is now complete with the correct result
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals([0 => 10, 2 => 12], $task->getResult());
    }
    
    /**
     * Tests that a SomeTask completes with a failure when it is not possible for
     * enough subtasks to complete successfully
     */
    public function testFailsWhenEnoughSubTasksFail() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        // Ask for 2 successful completions before we get a result
        $task = new SomeTask($tasks, 2);
        
        // Check that the task is not complete
        $this->assertFalse($task->isComplete());
        
        // Fail the first task and tick
        $tasks[0]->setException(new \Exception("failure 1"));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete
        $this->assertFalse($task->isComplete());
        
        // Fail another task and tick
        $tasks[1]->setException(new \Exception("failure 2"));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete, as it is still possible for
        // two tasks to complete successfully
        $this->assertFalse($task->isComplete());
        
        // Fail a third task and tick
        $tasks[3]->setException(new \Exception("failure 3"));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task has now failed with the correct failure
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        $this->assertInstanceOf(
            MultipleFailureException::class, $task->getException()
        );
        $this->assertContains('3 tasks failed', $task->getException()->getMessage());
        $this->assertEquals(
            [
                0 => new \Exception("failure 1"),
                1 => new \Exception("failure 2"),
                3 => new \Exception("failure 3")
            ],
            $task->getException()->getFailures()
        );
    }
    
    /**
     * Test that a SomeTask completes successfully if enough subtasks complete
     * successfully, even if some subtasks fail
     */
    public function testCompletesWithAcceptableSubTaskFailures() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        // Ask for 2 successful completions before we get a result
        $task = new SomeTask($tasks, 2);
        
        // Check that the task is not complete
        $this->assertFalse($task->isComplete());
        
        // Complete the first task and tick
        $tasks[0]->setResult(10);
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete
        $this->assertFalse($task->isComplete());
        
        // Fail a task and tick
        $tasks[1]->setException(new \Exception("failure"));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete
        $this->assertFalse($task->isComplete());
        
        // Complete a second task and tick
        $tasks[2]->setResult(12);
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is now complete with the correct result
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals([0 => 10, 2 => 12], $task->getResult());
    }
    
    /**
     * Test that a SomeTask fails when it is no longer possible for $howMany
     * tasks to complete successully, even if there are already successful subtasks
     */
    public function testFailsEvenWithSuccessfulSubTasks() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        // Ask for 2 successful completions before we get a result
        $task = new SomeTask($tasks, 2);
        
        // Check that the task is not complete
        $this->assertFalse($task->isComplete());
        
        // Fail the first task and tick
        $tasks[0]->setException(new \Exception("failure 1"));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete
        $this->assertFalse($task->isComplete());
        
        // Complete a task successfully and tick
        $tasks[2]->setResult(10);
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete
        $this->assertFalse($task->isComplete());        
        
        // Fail a second task and tick
        $tasks[1]->setException(new \Exception("failure 2"));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is still not complete, as it is still possible for
        // two tasks to complete successfully
        $this->assertFalse($task->isComplete());
        
        // Fail a third task and tick
        $tasks[3]->setException(new \Exception("failure 3"));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task has now failed with the correct failure
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        $this->assertInstanceOf(
            MultipleFailureException::class, $task->getException()
        );
        $this->assertContains('3 tasks failed', $task->getException()->getMessage());
        $this->assertEquals(
            [
                0 => new \Exception("failure 1"),
                1 => new \Exception("failure 2"),
                3 => new \Exception("failure 3")
            ],
            $task->getException()->getFailures()
        );
    }
    
    /**
     * Check that completing/failing additional subtasks once the task is complete
     * doesn't affect the result
     */
    public function testCompletingAdditionalSubTasksDoesntAffectResult() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        // Ask for 2 successful completions before we get a result
        $task = new SomeTask($tasks, 2);
        
        // Complete the first two tasks and check the task is complete with the
        // expected result
        $tasks[0]->setResult(10);
        $tasks[1]->setResult(11);
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals([0 => 10, 1 => 11], $task->getResult());
        
        // Complete the third task and check that the result is unaffected
        $tasks[2]->setResult(12);
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals([0 => 10, 1 => 11], $task->getResult());
        
        // Fail the fourth task and check the result is unaffected
        $tasks[3]->setException(new \Exception("failure"));
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals([0 => 10, 1 => 11], $task->getResult());
    }
    
    /**
     * Tests that once a SomeTask has failed, the failure cannot be affected by
     * completing/failing more subtasks
     */
    public function testCompletingAdditionalSubTasksDoesntAffectFailure() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(),
                  new TaskStub(), new TaskStub(), new TaskStub()];
        
        // Ask for 3 successful completions before we get a result
        $task = new SomeTask($tasks, 3);
        
        // Fail the first four tasks and check the task is failed with the
        // expected exception
        $tasks[0]->setException(new \Exception("failure 1"));
        $tasks[1]->setException(new \Exception("failure 2"));
        $tasks[2]->setException(new \Exception("failure 3"));
        $tasks[3]->setException(new \Exception("failure 4"));
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        $this->assertInstanceOf(
            MultipleFailureException::class, $task->getException()
        );
        $this->assertContains('4 tasks failed', $task->getException()->getMessage());
        $this->assertEquals(
            [
                0 => new \Exception("failure 1"),
                1 => new \Exception("failure 2"),
                2 => new \Exception("failure 3"),
                3 => new \Exception("failure 4")
            ],
            $task->getException()->getFailures()
        );
        
        // Complete the fifth task and check that the failure is unaffected
        $tasks[4]->setResult(14);
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        $this->assertInstanceOf(
            MultipleFailureException::class, $task->getException()
        );
        $this->assertContains('4 tasks failed', $task->getException()->getMessage());
        $this->assertEquals(
            [
                0 => new \Exception("failure 1"),
                1 => new \Exception("failure 2"),
                2 => new \Exception("failure 3"),
                3 => new \Exception("failure 4")
            ],
            $task->getException()->getFailures()
        );
        
        // Fail the sixth task and check the failure is unaffected
        $tasks[5]->setException(new \Exception("failure 6"));
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        $this->assertInstanceOf(
            MultipleFailureException::class, $task->getException()
        );
        $this->assertContains('4 tasks failed', $task->getException()->getMessage());
        $this->assertEquals(
            [
                0 => new \Exception("failure 1"),
                1 => new \Exception("failure 2"),
                2 => new \Exception("failure 3"),
                3 => new \Exception("failure 4")
            ],
            $task->getException()->getFailures()
        );
    }
    
    /**
     * Tests that a cancelled task is treated as a failure
     */
    public function testTaskCancellationCountsAsFailure() 
	{
        $tasks = [new TaskStub(), new TaskStub()];
        
        // Ask for all tasks to complete successfully for a result
        $task = new SomeTask($tasks, 2);
        
        // Check that the task is not complete
        $this->assertFalse($task->isComplete());
        
        // Cancel the first task and tick
        $tasks[0]->cancel();
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is now complete
        $this->assertTrue($task->isComplete());
        // With an error
        $this->assertTrue($task->isFaulted());
        // And that the error is a TaskCancelledException
        $this->assertInstanceOf(
            TaskCancelledException::class,
            $task->getException()->getFailures()[0]
        );
        // And that the correct task is in the exception
        $this->assertSame(
            $tasks[0], $task->getException()->getFailures()[0]->getCancelledTask()
        );
    }
}
