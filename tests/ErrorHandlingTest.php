<?php

namespace Async\Tests;

use Async\Coroutine\Parallel;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

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

    public function testIt_throws_the_exception_if_no_catch_callback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/test/');

        $parallel = new Parallel();

        $parallel->add(function () {
            throw new \Exception('test');
        });

        $parallel->wait();
    }

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
