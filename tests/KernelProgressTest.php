<?php

namespace Async\Tests;

use Async\Spawn\Channel;
use PHPUnit\Framework\TestCase;

class KernelProgressTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function taskSpawnProgress()
    {
        $channel = new Channel;
        $realTimeTask = yield \progress_task(function ($type, $data) use ($channel) {
            $this->assertNotNull($type);
            $this->assertNotNull($data);
        });

        $realTime = yield \spawn_progress(function () {
            echo 'hello ';
            usleep(2000);
            return 'world';
        }, $channel, $realTimeTask);

        $notUsing = yield \gather($realTime);
        yield \shutdown();
    }

    public function testSpawnProgress()
    {
        \coroutine_run($this->taskSpawnProgress());
    }

}
