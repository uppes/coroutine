<?php

namespace Async\Tests;

use Error;
use ParseError;
use Async\Coroutine\Parallel;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::getFailed
     * @covers Async\Coroutine\Parallel::status
     */
    public function testIt_can_handle_exceptions_via_catch_callback()
    {
        $parallel = new Parallel();

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
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     */
    public function testIt_throws_the_exception_if_no_catch_callback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/test/');

        $parallel = new Parallel();

        $parallel->add(function () {
            throw new \Exception('test');
        });

        $parallel->wait();
    }

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     */
    public function testIt_keeps_the_original_trace()
    {
        $parallel = new Parallel();

        $parallel->add(function () {
            $myClass = new MyClass();

            $myClass->throwException();
        })->catch(function (\Exception $exception) {
            $this->assertStringContainsString('Async\Tests\MyClass->throwException()', $exception->getMessage());
        });

        $parallel->wait();
    }
}
