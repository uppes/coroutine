<?php

namespace Async\Tests;

use Async\Coroutine\Coroutine;
use Async\Coroutine\Kernel;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\CoroutineInterface;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    protected function controller()
    {
        $onAlreadyCompleted = function (TaskInterface $tasks) {
            $tasks->customState('done');
            $tasks->getCustomData();

            return $tasks->result();
        };

        $onNotStarted = function (TaskInterface $tasks, CoroutineInterface $coroutine) {
            $tasks->customState();
            $coroutine->schedule($tasks);
            $coroutine->execute();
        };

        $onCompleted = function (TaskInterface $tasks) {
            $tasks->customState('done');
            $tasks->getCustomData();

            return $tasks->result();
        };

        $onToClear = function (TaskInterface $tasks) {
            $tasks->customState('cleared');
            $tasks->getCustomData();
        };

        $onError = null;
        $onCancel = null;

        Kernel::gatherController(
            '',
            $onAlreadyCompleted,
            $onNotStarted,
            $onCompleted,
            $onError,
            $onCancel,
            $onToClear
        );
    }

    public function factorial($name, $number)
    {
        $f = 1;
        foreach (range(2, $number + 1) as $i) {
            yield \sleep_for(1);
            $f *= $i;
        }

        return $f;
    }

    public function taskGather()
    {
        $this->controller();
        \gather_options(2);
        $factorials = yield \gather(
            $this->factorial("A", 2),
            $this->factorial("B", 3),
            $this->factorial("C", 4)
        );

        $this->assertNotEmpty($factorials);
        $this->assertCount(2, $factorials);
    }

    public function testGather()
    {
        \coroutine_run($this->taskGather());
    }

    public function lapse(int $taskId = null)
    {
        yield \cancel_task($taskId);
    }

    public function keyboard()
    {
        $tid = yield \task_id();
        yield \await($this->lapse($tid));
        return yield Coroutine::input(256);
    }

    public function taskSleepFor()
    {
        $t0 = \microtime(true);
        $done = yield Kernel::sleepFor(1, 'done sleeping');
        $t1 = \microtime(true);
        $this->assertEquals('done sleeping', $done);
        $this->assertGreaterThan(1, (float) ($t1 - $t0));
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
