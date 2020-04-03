<?php

namespace Async\Tests;

use Async\Spawn\Channel;
use Async\Coroutine\Exceptions\InvalidStateError;
use PHPUnit\Framework\TestCase;

class KernelSignalProgressTest extends TestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Progress an Signal subprocess tests skipped for now, causing code coverage submission issues.');
        \coroutine_clear();
    }

    public function taskSpawnProgress()
    {
        echo __LINE__;
        $channel = new Channel;
        $realTimeTask = \progress_task(function ($type, $data) {
            echo __LINE__;
            $this->assertNotNull($type);
            $this->assertNotNull($data);
        });

        $realTime = yield \spawn_progress(function () {
            echo 'hello ';
            usleep(500);
            return 'world';
        }, $channel, yield $realTimeTask, 1);

        $notUsing = yield \gather($realTime);
        yield \shutdown();
    }

    public function DoNotTestSpawnProgress()
    {
        \coroutine_run($this->taskSpawnProgress());
    }

    public function taskSpawnSignalDelay()
    {
        $sigTask = yield \signal_task(\SIGKILL, function ($signal) {
            $this->assertEquals(\SIGKILL, $signal);
        });

        $sigId = yield \spawn_signal(function () {
            \usleep(5000);
            return 'subprocess';
        }, \SIGKILL, $sigTask);

        $kill = yield \away(function () use ($sigId) {
            yield;
            $bool = yield \spawn_kill($sigId);
            return $bool;
        }, true);

        $output = yield \gather($sigId);
        //$output = yield \gather_wait([$sigId, $kill], 0, false);
        //$this->assertEquals([null, true], [$output[$sigId], $output[$kill]]);
    }

    public function testSpawnSignalDelay()
    {
        \coroutine_run($this->taskSpawnSignalDelay());
    }

    public function taskSpawnSignal()
    {
        $sigTask = yield \signal_task(\SIGKILL, function ($signal) {
            $this->assertEquals(\SIGKILL, $signal);
        });

        $sigId = yield \spawn_signal(function () {
            sleep(2);
            return 'subprocess';
        }, \SIGKILL, $sigTask, 1);

        yield \away(function () use ($sigId) {
            return yield \spawn_kill($sigId);
        });

        $this->expectException(InvalidStateError::class);
        yield \gather($sigId);
    }

    public function testSpawnSignal()
    {
        \coroutine_run($this->taskSpawnSignal());
    }
}
