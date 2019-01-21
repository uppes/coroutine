<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Coroutine\Tasks\DelayedTask;
use Async\Coroutine\Tasks\GeneratorTask;
use Async\Coroutine\Tasks\TaskCancelledException;
use Async\Tests\Tasks\TaskStub;
use Async\Tests\Tasks\GeneratorHelper;
use PHPUnit\Framework\TestCase;

class GeneratorTaskTest extends TestCase 
{
	use \Async\Tests\getMocker;
	
    /**
     * Test that a generator task with an empty generator is immediately complete
     */
    public function testTaskCompleteWithEmptyGenerator() {
        // Create an empty generator (i.e. don't give it a task to yield)
        $generator = new GeneratorHelper();
        
        $task = new GeneratorTask($generator->generator());
        
        // The task should be instantly complete, as the generator has no task
        // to yield
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
    }
    
    /**
     * Test that tasks yielded by the generator are added to the scheduler
     */
    public function testYieldedTaskIsScheduled() 
	{
        $generator = new GeneratorHelper();
        
        // Create a stub task to be yielded
        $stub = new TaskStub();
        $generator->yieldTask($stub);
        
        // Create the generator task
        $task = new GeneratorTask($generator->generator());
        
        // Check that the task is not complete
        $this->assertFalse($task->isComplete());
        
        // Create a mock scheduler that expects the yielded task to be scheduled
        $scheduler = $this->getMock(SchedulerInterface::class);
        $scheduler->expects($this->once())->method('schedule')->with($stub);
        
        // Tick the task and check that the task gets scheduled
        $task->tick($scheduler);
    }
    
    /**
     * Test that the result from a completed task is sent back to the generator
     */
    public function testTaskResultSent() 
	{
        $generator = new GeneratorHelper();
        
        // Create a stub task to be yielded
        $stub = new TaskStub();
        $generator->yieldTask($stub);
        
        // Create the generator task under test
        $task = new GeneratorTask($generator->generator());
        // Tick the generator task to receive the first yielded task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is not complete and that nothing has been sent
        // back to the generator yet
        $this->assertFalse($task->isComplete());
        $this->assertNull($generator->current());
        
        // Complete the yielded task with a result
        $stub->setResult("result");
        
        // Tick the task and verify that the generator received the result
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertEquals("result", $generator->current());
    }
    
    /**
     * Test that the exception from a failed task is thrown into the generator
     */
    public function testTaskErrorThrown() 
	{
        $generator = new GeneratorHelper();
        
        // Create a stub task to be yielded
        $stub = new TaskStub();
        $generator->yieldTask($stub);
        
        // Create the generator task under test
        $task = new GeneratorTask($generator->generator());
        // Tick the generator task to receive the first yielded task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is not complete and that nothing has been sent
        // back to the generator yet
        $this->assertFalse($task->isComplete());
        $this->assertNull($generator->current());
        $this->assertNull($generator->caughtException());
        
        // Complete the yielded task with a failure
        $stub->setException(new \Exception('failure'));
        
        // Tick the task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // The generator should have received an exception
        $this->assertNull($generator->current());
        $this->assertEquals('failure', $generator->caughtException()->getMessage());
    }
    
    /**
     * Test that a TaskCancelledException is thrown into the generator when the
     * yielded task is cancelled
     */
    public function testCancelledTaskErrorThrown() 
	{
        $generator = new GeneratorHelper();
        
        // Create a stub task to be yielded
        $stub = new TaskStub();
        $generator->yieldTask($stub);
        
        // Create the generator task under test
        $task = new GeneratorTask($generator->generator());
        // Tick the generator task to receive the first yielded task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is not complete and that nothing has been sent
        // back to the generator yet
        $this->assertFalse($task->isComplete());
        $this->assertNull($generator->current());
        $this->assertNull($generator->caughtException());
        
        // Cancel the yielded task
        $stub->cancel();
        
        // Tick the task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // The generator should have received a TaskCancelledException containing
        // the stub task
        $this->assertNull($generator->current());
        $this->assertInstanceOf(
            TaskCancelledException::class, $generator->caughtException()
        );
        $this->assertSame($stub, $generator->caughtException()->getCancelledTask());
    }
    
    /**
     * Test that the result of a yielded task is correctly returned to the generator
     * even when the task takes several ticks to complete
     */
    public function testDelayedTaskCompletion() 
	{
        $generator = new GeneratorHelper();
        
        // Create a stub task to be yielded
        $stub = new TaskStub();
        $generator->yieldTask($stub);
        
        // Create the generator task under test
        $task = new GeneratorTask($generator->generator());
        // Tick the generator task to receive the first yielded task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Tick the task several times
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Verify that nothing has been sent back to the generator yet
        $this->assertFalse($task->isComplete());
        $this->assertNull($generator->current());
        
        // Complete the task with a result
        $stub->setResult('result');
        
        // Tick the generator task again
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Verify that current has been set
        $this->assertEquals('result', $generator->current());
    }
    
    /**
     * Test that the generator successfully yields and receives a return value from
     * more than one task, and completes successfully when the last one completes
     */
    public function testMultipleYieldedTasks() 
	{
        $generator = new GeneratorHelper();
        
        // Create the first stub task to be yielded
        $stub1 = new TaskStub();
        $generator->yieldTask($stub1);
        
        // Create the generator task under test
        $task = new GeneratorTask($generator->generator());
        // Tick the generator task to receive the first yielded task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Complete the first yielded task with a result
        $stub1->setResult("stub result 1");
        
        // Create the second stub task to be yielded
        $stub2 = new TaskStub();
        $generator->yieldTask($stub2);
        
        // Check that the task is not complete and that nothing has been sent
        // back to the generator yet
        $this->assertFalse($task->isComplete());
        $this->assertNull($generator->current());
        
        // Tick the task and verify that the generator received the result of the
        // first stub task
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertEquals("stub result 1", $generator->current());
        
        // Complete the second yielded task with a result
        $stub2->setResult("stub result 2");
        
        // Check that the task is not complete and that the second result has not
        // yet been sent to the generator
        $this->assertFalse($task->isComplete());
        $this->assertEquals("stub result 1", $generator->current());
        
        // Tick the generator task
        $task->tick($this->getMock(SchedulerInterface::class));
        // Verify that the generator received the result of the second task
        $this->assertEquals("stub result 2", $generator->current());
        // Veriry that the generator task completed with no error
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        // For completeness, test that the result is null
       // $this->assertNull($task->getResult());
    }
    
    /**
     * Tests that throwing an exception from the generator results in task failure
     * with the given exception as the reason
     */
    public function testTaskFailureOnUncaughtException() 
	{
        $generator = new GeneratorHelper();
        
        // Create the stub task to be yielded
        $stub = new TaskStub();
        $generator->yieldTask($stub);
        
        // Create the generator task under test
        $task = new GeneratorTask($generator->generator());
        // Tick the generator task to receive the yielded task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Complete the first yielded task with a result
        $stub->setResult("result");
        
        // Set the exception to throw when the generator is resumed
        $generator->throwException(new \Exception('thrown'));
        
        // Check that the task is not complete
        $this->assertFalse($task->isComplete());
        
        // Tick the generator task and verify that it has failed with the
        // thrown exception as the reason
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertTrue($task->isFaulted());
        $this->assertEquals('thrown', $task->getException()->getMessage());
    }
    
    /**
     * Test that ticking the generator task after the generator has finished
     * has no effect
     */
    public function testTickAfterTaskCompletionIsNullOperation() 
	{
        $generator = new GeneratorHelper();
        
        // Create a stub task to be yielded
        $stub = new TaskStub();
        $generator->yieldTask($stub);
        
        // Create the generator task under test
        $task = new GeneratorTask($generator->generator());
        // Tick the generator task to receive the first yielded task
        $task->tick($this->getMock(SchedulerInterface::class));
        
        // Check that the task is not complete and that nothing has been sent
        // back to the generator yet
        $this->assertFalse($task->isComplete());
        
        // Complete the yielded task with a result
        $stub->setResult("result");
        
        // Tick the generator task and check that the task is complete and that
        // the result was received by the generator
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertTrue($task->isComplete());
        $this->assertEquals('result', $generator->current());
        
        // Tick the task a few more times and verify that the current value of
        // the generator is still the same
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        $task->tick($this->getMock(SchedulerInterface::class));
        
        $this->assertTrue($task->isComplete());
        $this->assertEquals('result', $generator->current());
        
    }
}
