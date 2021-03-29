<?php

namespace Async\Tests;

use Async\Coroutine\Fiber;
use PHPUnit\Framework\TestCase;

class FiberTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function fiberArgs()
    {
        $fiber = new Fiber(function (int $x) {
            return ($x + yield Fiber::suspend($x));
        });

        yield $fiber->start(1);
        yield $fiber->resume(5);
        $this->assertEquals(6, $fiber->getReturn());
    }

    public function testArgs()
    {
        \coroutine_run($this->fiberArgs());
    }

    public function fiberResume()
    {
        $fiber = new Fiber(function () {
            $value = yield Fiber::suspend(1);
            $this->assertEquals(2, $value);
        });

        $value = yield $fiber->start();
        $this->assertEquals(1, $value);
        yield $fiber->resume($value + 1);
    }

    public function testResume()
    {
        \coroutine_run($this->fiberResume());
    }

    public function fiberCatch()
    {
        $fiber = new Fiber(function () {
            try {
                yield Fiber::suspend('test');
            } catch (\Exception $exception) {
                $this->assertEquals('test', $exception->getMessage());
            }
        });

        $value = yield $fiber->start();
        $this->assertEquals('test', $value);

        yield $fiber->throw(new \Exception('test'));
    }

    public function testCatch()
    {
        \coroutine_run($this->fiberCatch());
    }

    public function fiberGetReturn()
    {
        $fiber = new Fiber(function () {
            $value = yield Fiber::suspend(1);
            return $value;
        });

        $value = yield $fiber->start();
        $this->assertEquals(1, $value);
        $this->assertNull(yield $fiber->resume($value + 1));
        $this->assertEquals(2, $fiber->getReturn());
    }

    public function testGetReturn()
    {
        \coroutine_run($this->fiberGetReturn());
    }

    public function fiberStatus()
    {
        $fiber = new Fiber(function () {
            $fiber = Fiber::this();
            $this->assertTrue($fiber->isStarted());
            $this->assertTrue($fiber->isRunning());
            $this->assertFalse($fiber->isSuspended());
            $this->assertFalse($fiber->isTerminated());
            yield Fiber::suspend();
        });

        $this->assertFalse($fiber->isStarted());
        $this->assertFalse($fiber->isRunning());
        $this->assertFalse($fiber->isSuspended());
        $this->assertFalse($fiber->isTerminated());

        yield $fiber->start();

        $this->assertTrue($fiber->isStarted());
        $this->assertFalse($fiber->isRunning());
        $this->assertTrue($fiber->isSuspended());
        $this->assertFalse($fiber->isTerminated());

        yield $fiber->resume();

        $this->assertTrue($fiber->isStarted());
        $this->assertFalse($fiber->isRunning());
        $this->assertFalse($fiber->isSuspended());
        $this->assertTrue($fiber->isTerminated());
    }

    public function testStatus()
    {
        /**
         * --EXPECT--
         *bool(false) / before starting
         *bool(false)
         *bool(false)
         *bool(false)
         *bool(true) / inside fiber
         *bool(true)
         *bool(false)
         *bool(false)
         *bool(true) / after suspending
         *bool(false)
         *bool(true)
         *bool(false)
         *bool(true) / after resuming
         *bool(false)
         *bool(false)
         *bool(true)
         */
        \coroutine_run($this->fiberStatus());
    }
}
