<?php

namespace Async\Tests;

use PHPUnit\Framework\TestCase;

class NetworkTest extends TestCase
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

    public function taskNetworkAddress()
    {
        yield \away($this->counterTask());

        $ipString = yield \dns_address('yahoo.com');
        $this->assertTrue(\is_string($ipString));
        $ipInt = \ip2long($ipString);
        $this->assertTrue(\is_int($ipInt));
        $this->assertGreaterThanOrEqual(3, $this->counterResult);
        $name = yield \dns_name($ipString);
        $this->assertTrue(\is_string($ipString));
        $this->assertTrue(\strpos($name, 'yahoo.com') !== false);
        $this->assertGreaterThanOrEqual(6, $this->counterResult);

        yield \shutdown();
    }

    public function testDNSNetworkAddress()
    {
        \coroutine_run($this->taskNetworkAddress());
    }

    public function taskNetworkAddressBad()
    {
        yield \away($this->counterTask());

        $name = yield \dns_name('127.0.-.x');
        $this->assertFalse($name);
        $this->assertGreaterThanOrEqual(1, $this->counterResult);
        $ipString = yield \dns_address('--yahoo.com');
        $this->assertFalse($ipString);
        $this->assertGreaterThanOrEqual(1, $this->counterResult);

        yield \shutdown();
    }

    public function testDNSNetworkAddressBad()
    {
        \coroutine_run($this->taskNetworkAddressBad());
    }

    public function taskNetworkRecord()
    {
        yield \away($this->counterTask());

        $records = yield \dns_record('yahoo.com');
        $this->assertTrue(\is_array($records));
        $this->assertGreaterThanOrEqual(4, $this->counterResult);
        $this->assertEquals('yahoo.com', $records[0]['host']);
        $this->assertEquals('A', $records[5]['type']);
        $ipString = yield \dns_record('--yahoo.com');
        $this->assertFalse($ipString);
        $this->assertGreaterThanOrEqual(5, $this->counterResult);

        yield \shutdown();
    }

    public function testDNSNetworkRecord()
    {
        \coroutine_run($this->taskNetworkRecord());
    }
}
