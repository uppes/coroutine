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
        });

        $realTime = yield \spawn_progress(function () {
            echo 'hello ';
            return \return_in((\IS_LINUX ? 50 : 3000), 'world');
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
            return \return_in((\IS_LINUX ? 100 : 5500), 'world');
        }, $channel, $realTimeTask);

        $result = yield \gather($realTime);
        $this->assertEquals('world', $result[$realTime]);
        yield \shutdown();
    }

    public function testSpawnProgressResult()
    {
        \coroutine_run($this->taskSpawnProgressResult());
    }
}
