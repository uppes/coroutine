<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Channel;
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    protected $counterResult = null;

	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function counterTask()
    {
        $counter = 0;
        while (true) {
            $counter++;
            $this->counterResult = $counter;
            yield;
        }
    }

    public function taskSender(Channel $channel, int $tid)
    {
        $this->assertTrue($channel instanceof Channel);
        yield \sender($channel, 'true', $tid);
    }

    public function taskSenderAgain(Channel $channelAgain)
    {
        $message = yield \receiver($channelAgain);
        $this->assertEquals('true', $message);
    }

    public function taskMake()
    {
        yield \away($this->counterTask());
        $channel = yield \make();
        $tid = yield \go($this->taskSenderAgain($channel));
        yield \go($this->taskSender($channel, $tid));

        $again = yield \sender($channel, 'again', 1);
        $this->assertEquals('again', $again);
        $this->assertEquals(5, $this->counterResult);

        yield \shutdown();
    }

    public function testMake()
    {
        \coroutine_run($this->taskMake());
    }
}
