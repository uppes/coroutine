<?php

namespace Async\Tests;

use Async\Channel;
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    protected $counterResult = null;

    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function taskSenderMain(Channel $channel)
    {
        yield \sleep_for(1);
        $this->assertTrue($channel instanceof Channel);
        yield \sender($channel, 'done');
    }

    public function taskMakeMain()
    {
        $channel = yield \make();
        yield \go($this->taskSenderMain($channel));

        $message = yield \receiver($channel);
        $this->assertEquals('done', $message);

        yield \shutdown();
    }

    public function testMakeMain()
    {
        \coroutine_run($this->taskMakeMain());
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
        yield \sender($channel, 'try again');
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

        $message = yield \receiver($channel);
        $this->assertEquals('try again', $message);

        $again = yield \sender($channel, 'again', 1);
        $this->assertEquals('again', $again);
        $this->assertEquals(8, $this->counterResult);

        $message = yield \receiver($channel);
        $this->assertNull($message);
        $this->assertEquals(9, $this->counterResult);

        yield \shutdown();
    }

    public function testMake()
    {
        \coroutine_run($this->taskMake());
    }
}
