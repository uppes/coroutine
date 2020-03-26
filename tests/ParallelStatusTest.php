<?php

namespace Async\Tests;

use Async\Coroutine\Coroutine;
use Async\Coroutine\Parallel;
use Async\Tests\MyTask;
use PHPUnit\Framework\TestCase;

class ParallelStatusTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function testIt_can_show_a_textual_status()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $parallel->add(function () {
            sleep(5);
        });

        $this->assertStringContainsString('finished: 0', (string) $parallel->status());

        $parallel->wait();

        $this->assertStringContainsString('finished: 1', (string) $parallel->status());
    }

    public function testIt_can_show_a_textual_failed_status()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                throw new \Exception('Test');
            })->catch(function () {
                // Do nothing
            });
        }

        $parallel->wait();

		$this->assertStringContainsString('finished: 0', (string) $parallel->status());
		$this->assertStringContainsString('failed: 5', (string) $parallel->status());
		$this->assertStringContainsString('failed with Exception: Test', (string) $parallel->status());
    }

    public function testIt_can_show_timeout_status()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                sleep(1000);
            }, 1);
        }

        $parallel->wait();

        $this->assertStringContainsString('timeout: 5', (string) $parallel->status());
    }
}
