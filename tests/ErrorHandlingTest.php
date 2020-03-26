<?php

namespace Async\Tests;

use Async\Coroutine\Coroutine;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function testIt_can_handle_exceptions_via_catch_callback()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                throw new \Exception('test');
            })->catch(function (\Exception $e) {
                $this->assertRegExp('/test/', $e->getMessage());
            });
        }

        $parallel->wait();

        $this->assertCount(5, $parallel->getFailed(), (string) $parallel->status());

        $coroutine->shutdown();
    }

    public function testIt_throws_the_exception_if_no_catch_callback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/test/');

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $parallel->add(function () {
            throw new \Exception('test');
        });

        $parallel->wait();

        $coroutine->shutdown();
    }

    public function testIt_keeps_the_original_trace()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $parallel->add(function () {
            $myClass = new MyClass();

            $myClass->throwException();
        })->catch(function (\Exception $exception) {
            $this->assertStringContainsString('Async\Tests\MyClass->throwException()', $exception->getMessage());
        }, 1);

        $parallel->wait();

        $coroutine->shutdown();
    }
}
