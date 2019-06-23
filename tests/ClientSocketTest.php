<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\ClientSocket;
use Async\Coroutine\ClientSocketInterface;
use PHPUnit\Framework\TestCase;

class ClientSocketTest extends TestCase
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
        $this->assertTrue($socket instanceof ClientSocketInterface);
    
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
     * @covers Async\Coroutine\ClientSocket::read
     * @covers Async\Coroutine\ClientSocket::write
     * @covers Async\Coroutine\ClientSocket::close
     * @covers Async\Coroutine\ClientSocket::meta
     * @covers Async\Coroutine\ClientSocket::create
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
        \coroutine_run($this->taskClient('https://facebook.com', 443, '/'));
    }
}
