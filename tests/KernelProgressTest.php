<?php

namespace Async\Tests;

use Async\Spawn\Channeled;
use Async\Spawn\ChanneledInterface;
use PHPUnit\Framework\TestCase;

class KernelProgressTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function taskSpawnProgress()
    {
        $channel = new Channeled;
        $realTimeTask = yield \progress_task(function ($type, $data) {
            $this->assertNotNull($type);
            $this->assertNotNull($data);
            yield;
        });

        $realTime = yield \spawn_progress(function () {
            echo 'hello ';
            \returning(2500);
            return 'world';
        }, $channel, $realTimeTask);

        $notUsing = yield \gather($realTime);
        yield \shutdown();
    }

    public function testSpawnProgress()
    {
        \coroutine_run($this->taskSpawnProgress());
    }

    public function taskSpawnProgressResult()
    {
        $channel = new Channeled;
        $realTimeTask = yield \progress_task(function ($type, $data) use ($channel) {
            $this->assertNotNull($type);
            $this->assertNotNull($data);
        });

        $realTime = yield \spawn_progress(function (ChanneledInterface $ipc) {
            $ipc->write('hello ');

            \returning(5500);
            return 'world';
        }, $channel, $realTimeTask);

        $result = yield \gather($realTime);
        $this->assertEquals('world', $result[$realTime]);
    }

    public function testSpawnProgressResult()
    {
        \coroutine_run($this->taskSpawnProgressResult());
    }
}
