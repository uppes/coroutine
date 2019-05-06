<?php

namespace Async\Tests;

use Async\Coroutine\Spawn;
use Async\Tests\MyTask;
use PHPUnit\Framework\TestCase;

class SpawnStatusTest extends TestCase
{
    /**
     * @covers Async\Coroutine\Coroutine::initProcess
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Process::init
     * @covers Async\Coroutine\Spawn::markAsFinished
     * @covers Async\Coroutine\Spawn::markAsTimedOut
     * @covers Async\Coroutine\Spawn::markAsFailed
     * @covers Async\Coroutine\Spawn::__construct
     * @covers Async\Coroutine\Spawn::add
     * @covers Async\Coroutine\Spawn::wait
     * @covers Async\Coroutine\Spawn::status
     * @covers Async\Coroutine\SpawnStatus::__construct
     * @covers Async\Coroutine\SpawnStatus::summaryToString
     */
    public function testIt_can_show_a_textual_status()
    {
        $parallel = new Spawn();

        $parallel->add(function () {
            sleep(5);
        });

        $this->assertStringContainsString('finished: 0', (string) $parallel->status());

        $parallel->wait();

        $this->assertStringContainsString('finished: 1', (string) $parallel->status());
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
     * @covers Async\Coroutine\Spawn::status
     * @covers Async\Coroutine\SpawnStatus::__construct
     * @covers Async\Coroutine\SpawnStatus::summaryToString
     */
    public function testIt_can_show_a_textual_failed_status()
    {
        $parallel = new Spawn();

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
     * @covers Async\Coroutine\Spawn::status
     * @covers Async\Coroutine\SpawnStatus::__construct
     * @covers Async\Coroutine\SpawnStatus::summaryToString
     */
    public function testIt_can_show_timeout_status()
    {
        $parallel = new Spawn();
        
        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                sleep(1000);
            }, 1);
        }

        $parallel->wait();

        $this->assertStringContainsString('timeout: 5', (string) $parallel->status());
    }
}
