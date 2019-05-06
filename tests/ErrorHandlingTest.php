<?php

namespace Async\Tests;

use Error;
use ParseError;
use Async\Coroutine\Spawn;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
	protected function setUp(): void
    {
        global $__coroutine__;
        
        $__coroutine__ = null;
    }

    /**
     * @covers Async\Coroutine\Coroutine::initProcess
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Process::init
     * @covers Async\Coroutine\Spawn::markAsFinished
     * @covers Async\Coroutine\Spawn::markAsTimedOut
     * @covers Async\Coroutine\Spawn::markAsFailed
     * @covers Async\Coroutine\Spawn::add
     * @covers Async\Coroutine\Spawn::__construct
     * @covers Async\Coroutine\Spawn::wait
     * @covers Async\Coroutine\Spawn::getFailed
     * @covers Async\Coroutine\Spawn::status
     */
    public function testIt_can_handle_exceptions_via_catch_callback()
    {
        $parallel = new Spawn();

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                throw new \Exception('test');
            })->catch(function (\Exception $e) {
                $this->assertRegExp('/test/', $e->getMessage());
            });
        }

        $parallel->wait();

        $this->assertCount(5, $parallel->getFailed(), (string) $parallel->status());
    }

    /**
     * @covers Async\Coroutine\Coroutine::initProcess
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Process::init
     * @covers Async\Coroutine\Spawn::markAsFinished
     * @covers Async\Coroutine\Spawn::markAsTimedOut
     * @covers Async\Coroutine\Spawn::markAsFailed
     * @covers Async\Coroutine\Spawn::add
     * @covers Async\Coroutine\Spawn::__construct
     * @covers Async\Coroutine\Spawn::wait
     */
    public function testIt_throws_the_exception_if_no_catch_callback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/test/');

        $parallel = new Spawn();

        $parallel->add(function () {
            throw new \Exception('test');
        });

        $parallel->wait();
    }

    /**
     * @covers Async\Coroutine\Coroutine::initProcess
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Process::init
     * @covers Async\Coroutine\Spawn::markAsFinished
     * @covers Async\Coroutine\Spawn::markAsTimedOut
     * @covers Async\Coroutine\Spawn::markAsFailed
     * @covers Async\Coroutine\Spawn::add
     * @covers Async\Coroutine\Spawn::__construct
     * @covers Async\Coroutine\Spawn::wait
     */
    public function testIt_keeps_the_original_trace()
    {
        $parallel = new Spawn();

        $parallel->add(function () {
            $myClass = new MyClass();

            $myClass->throwException();
        })->catch(function (\Exception $exception) {
            $this->assertStringContainsString('Async\Tests\MyClass->throwException()', $exception->getMessage());
        });

        $parallel->wait();
    }
}
