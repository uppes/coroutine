<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\StreamSocket;
use Async\Coroutine\StreamSocketInterface;
use PHPUnit\Framework\TestCase;

class ClientStreamSocketTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function taskClient($hostname, $port, $command) {
        $contextOptions = array(
            'ssl' => array(
                'allow_self_signed' => true
            )
        );
    
        #Connect to Server
        #Start SSL
        $socket = yield \create_client("$hostname:$port", $contextOptions);
        $this->assertTrue($socket instanceof StreamSocketInterface);
    
        #Send a command
        $written = yield \client_write($socket, $command);
        $this->assertEquals('int', \is_type($written));
    
        $meta = \client_meta($socket);
        $this->assertEquals('array', \is_type($meta));

        #Receive response from server. Loop until the response is finished
        $response = yield \client_read($socket);
        $this->assertEquals('string', \is_type($response));
    
        #close connection
        yield \client_close($socket);    
    }
    
    /**
     * @covers Async\Coroutine\Kernel::createTask
     * @covers Async\Coroutine\Kernel::readWait
     * @covers Async\Coroutine\Kernel::writeWait
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::addReader
     * @covers Async\Coroutine\Coroutine::removeReader
     * @covers Async\Coroutine\Coroutine::addWriter
     * @covers Async\Coroutine\Coroutine::removeWriter
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\StreamSocket::read
     * @covers Async\Coroutine\StreamSocket::write
     * @covers Async\Coroutine\StreamSocket::clientClose
     * @covers Async\Coroutine\StreamSocket::clientMeta
     * @covers Async\Coroutine\StreamSocket::createClient
     * @covers Async\Coroutine\StreamSocket::getMeta
     * @covers Async\Coroutine\Task::result
     * @covers Async\Coroutine\Task::rescheduled
     * @covers Async\Coroutine\Task::clearResult
     * @covers Async\Coroutine\Task::completed
     * @covers Async\Coroutine\Task::pending
     * @covers \create_client
     * @covers \client_write
     * @covers \client_read
     * @covers \client_close
     * @covers \client_meta
     * @covers \is_type
     */
    public function testClient() 
    {
        \coroutine_run($this->taskClient('https://facebook.com', 80, '/'));
    }
}
