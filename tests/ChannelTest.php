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

    /**
     * @covers Async\Coroutine\Kernel::make
     * @covers Async\Coroutine\Kernel::receiver
     * @covers Async\Coroutine\Kernel::receive
     * @covers Async\Coroutine\Channel::make
     * @covers Async\Coroutine\Channel::receiver
     * @covers Async\Coroutine\Channel::receive
     * @covers \go
     * @covers \sleep_for
     */
    public function testMake() 
    {
        \coroutine_run($this->taskMake());
    }
}
