<?php

namespace Async\Tests\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AnyTask;
use Async\Coroutine\Tasks\MultipleFailureException;
use PHPUnit\Framework\TestCase;
		
/**
 * NOTE that AnyTask is a subclass of SomeTask - only AnyTask specific functionality
 * is tested here (i.e. the requirement of only a single task to complete and the
 * result being the result of the completed task rather than a one-element array)
 * 
 * More stringent tests of SomeTask can be found in {@see \Async\Test\Task\SomeTaskTest}
 */
class AnyTaskTest extends TestCase 
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
     * Tests that an AnyTask completes successfully after one subtask completes
     * successfully, and that its result is the result of the completed task
     */
    public function testCompletesWithCorrectResultWhenOneSubTaskCompletes() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        $task = new AnyTask($tasks);
        
        $this->assertFalse($task->isComplete());
		
        // Fail a task and check that the task has not completed
        $tasks[0]->setException(new \Exception("failure"));
        $task->tick($this->getMock(SchedulerInterface::class));
        $this->assertFalse($task->isComplete());
        
        // Complete a task and check that the task completes successfully with
        // the correct result
        $tasks[1]->setResult(10);
        $task->tick($this->getMock(SchedulerInterface::class));
        
        $this->assertTrue($task->isComplete());
        $this->assertFalse($task->isFaulted());
        $this->assertEquals(10, $task->getResult());
    }
    
    /**
     * Tests that an AnyTask fails only when all the subtasks fail
     */
    public function testFailsWhenAllSubTasksFail() {
        $tasks = [new TaskStub(), new TaskStub(), new TaskStub(), new TaskStub()];
        
        $task = new AnyTask($tasks);
        
        // Fail each subtask in turn, checking that the task is not yet complete
        $i = 0;
        foreach( $tasks as $subTask ) {
            $i++;
            $this->assertFalse($task->isComplete());
            $subTask->setException(new \Exception("failure $i"));
            $task->tick($this->getMock(SchedulerInterface::class));
        }
        
        // Check that the task is now complete with an error
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
}
