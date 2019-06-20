<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\StreamSocket;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function lapse(int $taskId = null) 
    {
        yield \cancel_task($taskId);
    }

    public function keyboard()
    {
        $tid = yield \task_id();
        yield \await([$this, 'lapse'], $tid);
        return yield StreamSocket::input(256);
    }

    public function taskSleepFor() 
    {
        $t0 = \microtime(true);
        $done = yield Kernel::sleepFor(1, 'done sleeping');
        $t1 = \microtime(true);
        $this->assertEquals('done sleeping', $done);
        $this->assertGreaterThan(1, (float) ($t1-$t0));
    }

    public function taskInput() 
    {   
        try {
            $data = yield Kernel::gather($this->keyboard());
        } catch (\Async\Coroutine\Exceptions\CancelledError $e) {
            $this->assertInstanceOf(\Async\Coroutine\Exceptions\CancelledError::class, $e);
        }
    }

    public function taskWaitFor() 
    {        
        try {
            // Wait for at most 0.2 second
            yield Kernel::waitFor([$this, 'taskSleepFor'], 0.2);
        } catch (\Async\Coroutine\Exceptions\TimeoutError $e) {
            $this->assertInstanceOf(\Async\Coroutine\Exceptions\TimeoutError::class, $e);
        }
    }

    /**
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Kernel::createTask
     * @covers Async\Coroutine\Kernel::sleepFor
     * @covers Async\Coroutine\Task::getState
     * @covers Async\Coroutine\Task::exception
     * @covers \coroutine_run
     */
    public function testSleepFor() 
    {
        \coroutine_run($this->taskSleepFor());
    }

    /**
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Kernel::cancelTask
     * @covers Async\Coroutine\Kernel::waitFor
     */
    public function testWaitFor() 
    {
        \coroutine_run($this->taskWaitFor());
    }

    /**
     * @covers Async\Coroutine\Kernel::createTask
     * @covers Async\Coroutine\Kernel::cancelTask
     * @covers Async\Coroutine\Kernel::gather
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\StreamSocket::input
     * @covers Async\Coroutine\Task::result
     * @covers Async\Coroutine\Task::rescheduled
     * @covers Async\Coroutine\Task::clearResult
     * @covers Async\Coroutine\Task::completed
     * @covers Async\Coroutine\Task::pending
     * @covers Async\Coroutine\Task::cancelled
     * @covers Async\Coroutine\Task::isParallel
     * @covers Async\Coroutine\Task::erred
     */
    public function testInputAndGather() 
    {
        \coroutine_run($this->taskInput());
    }
}
