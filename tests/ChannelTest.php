<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\StreamSocket;
use Async\Coroutine\Channel;
use Async\Coroutine\StreamSocketInterface;
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
        yield Kernel::sender($channel, 'true');
    }
    
    public function taskMake() 
    {
        $channel = yield Kernel::make();
        $this->assertTrue($channel instanceof Channel);
        yield \go([$this, 'taskSender'], $channel);
        yield Kernel::receiver($channel);    
        $done = yield Kernel::receive($channel);
        $this->assertEquals('true', $done);
    }

    public function testMake() 
    {
        \coroutine_run($this->taskMake());
    }
}
