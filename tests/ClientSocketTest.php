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
    
    public function testClient() 
    {
        \coroutine_run($this->taskClient('https://facebook.com', 443, '/'));
    }
}
