<?php

namespace Async\Tests;

use Async\Coroutine\Parallel;
use Async\Tests\MyTask;
use PHPUnit\Framework\TestCase;

class ParallelStatusTest extends TestCase
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
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::status
     * @covers Async\Coroutine\ParallelStatus::__construct
     * @covers Async\Coroutine\ParallelStatus::summaryToString
     */
    public function testIt_can_show_a_textual_status()
    {
        $parallel = new Parallel();

        $parallel->add(function () {
            sleep(5);
        });

        $this->assertStringContainsString('finished: 0', (string) $parallel->status());

        $parallel->wait();

        $this->assertStringContainsString('finished: 1', (string) $parallel->status());
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
     * @covers Async\Coroutine\Parallel::status
     * @covers Async\Coroutine\ParallelStatus::__construct
     * @covers Async\Coroutine\ParallelStatus::summaryToString
     */
    public function testIt_can_show_a_textual_failed_status()
    {
        $parallel = new Parallel();

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
     * @covers Async\Coroutine\Parallel::status
     * @covers Async\Coroutine\ParallelStatus::__construct
     * @covers Async\Coroutine\ParallelStatus::summaryToString
     */
    public function testIt_can_show_timeout_status()
    {
        $parallel = new Parallel();
        
        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                sleep(1000);
            }, 1);
        }

        $parallel->wait();

        $this->assertStringContainsString('timeout: 5', (string) $parallel->status());
    }
}