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

    public function fiberThrow()
    {
        $fiber = new Fiber(function () {
            yield Fiber::suspend('test');
        });

        $value = yield $fiber->start();
        $this->assertEquals('test', $value);

        try {
            yield $fiber->throw(new \Exception('test'));
        } catch (\Throwable $e) {
            $this->assertEquals('Fatal error: Uncaught Exception: test in', $e->getMessage());
        }
    }

    public function testThrow()
    {
        $this->markTestSkipped('Test skipped.');
        \coroutine_run($this->fiberThrow());
    }
}
