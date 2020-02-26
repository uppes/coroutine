<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Channel;
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function taskSender(Channel $channel)
    {
        yield \sleep_for(2);
        $this->assertTrue($channel instanceof Channel);
        yield \sender($channel, 'true');
    }

    public function taskMake()
    {
        $channel = yield \make();
        $this->assertTrue($channel instanceof Channel);
        yield \go($this->taskSender($channel));
        $done = yield \receiver($channel);
        $this->assertEquals('true', $done);
        $false = yield \sender($channel, 'false', 1);
        $this->assertEquals('false', $false);
    }

    public function testMake()
    {
        \coroutine_run($this->taskMake());
    }
}
