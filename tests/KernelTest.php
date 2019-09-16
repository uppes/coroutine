<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Exceptions\Panicking;
use Async\Coroutine\FileStream;
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
        yield \await($this->lapse($tid));
        return yield FileStream::input(256);
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
            yield Kernel::waitFor($this->taskSleepFor(), 0.2);
        } catch (\Async\Coroutine\Exceptions\TimeoutError $e) {
            $this->assertInstanceOf(\Async\Coroutine\Exceptions\TimeoutError::class, $e);
            yield Kernel::shutdown();
        }
    }

    public function testSleepFor()
    {
        \coroutine_run($this->taskSleepFor());
    }

    public function testWaitFor()
    {
        \coroutine_run($this->taskWaitFor());
    }

    public function testInputAndGather()
    {
        \coroutine_run($this->taskInput());
    }

    public function testCancel()
    {
        $this->expectException(\InvalidArgumentException::class);
        \coroutine_run($this->lapse(99));
    }
}
