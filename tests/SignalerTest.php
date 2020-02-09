<?php

namespace Async\Tests;

use Async\Coroutine\UV;
use Async\Coroutine\Signaler;
use PHPUnit\Framework\TestCase;

class SignalerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!\function_exists('posix_kill') || !\function_exists('posix_getpid')) {
            if (!\function_exists('uv_loop_new'))
                $this->markTestSkipped(
                'Signal test skipped because functions "posix_kill" and "posix_getpid", or "uv_loop_new" are missing.'
                );
        }

        \coroutine_clear();
    }

    public function testEmittedEventsAndCallHandling()
    {
        $callCount = 0;
        $func = function () use (&$callCount) {
            $callCount++;
        };
        $signals = new Signaler(\coroutine_create());

        $this->assertSame(0, $callCount);

        $signals->add(UV::SIGUSR1, $func);
        $this->assertSame(0, $callCount);

        $signals->add(UV::SIGUSR1, $func);
        $this->assertSame(0, $callCount);

        $signals->add(UV::SIGUSR1, $func);
        $this->assertSame(0, $callCount);

        $signals->execute(UV::SIGUSR1);
        $this->assertSame(1, $callCount);

        $signals->add(UV::SIGUSR2, $func);
        $this->assertSame(1, $callCount);

        $signals->add(UV::SIGUSR2, $func);
        $this->assertSame(1, $callCount);

        $signals->execute(UV::SIGUSR2);
        $this->assertSame(2, $callCount);

        $signals->remove(UV::SIGUSR2, $func);
        $this->assertSame(2, $callCount);

        $signals->remove(UV::SIGUSR2, $func);
        $this->assertSame(2, $callCount);

        $signals->execute(UV::SIGUSR2);
        $this->assertSame(2, $callCount);

        $signals->remove(UV::SIGUSR1, $func);
        $this->assertSame(2, $callCount);

        $signals->execute(UV::SIGUSR1);
        $this->assertSame(2, $callCount);
    }
}
