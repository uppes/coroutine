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
     * @covers Async\Coroutine\Coroutine::createTask
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\Kernel::sleepFor
     * @covers \coroutine_run
     */
    public function testSleepFor() 
    {
        \coroutine_run($this->taskSleepFor());
    }

    /**
     * @covers Async\Coroutine\Coroutine::createTask
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\Kernel::waitFor
     * @covers \coroutine_run
     */
    public function testWaitFor() 
    {
        \coroutine_run($this->taskWaitFor());
    }

    /**
     * @covers Async\Coroutine\Coroutine::createTask
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\StreamSocket::input
     * @covers Async\Coroutine\Kernel::gather
     * @covers \coroutine_run
     */
    public function testInputAndGather() 
    {
        \coroutine_run($this->taskInput());
    }
}
