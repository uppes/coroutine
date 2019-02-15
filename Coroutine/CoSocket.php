<?php

namespace Async\Coroutine;

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoSocketInterface;

class CoSocket implements CoSocketInterface
{
    protected $socket;

    public function __construct($socket) 
	{
        $this->socket = $socket;
    }

    public function accept() 
	{
        yield Call::waitForRead($this->socket);
        yield Coroutine::value(new CoSocket(\stream_socket_accept($this->socket, 0)));
    }
	
    public function read(int $size) 
	{
        yield Call::waitForRead($this->socket);
        yield Coroutine::value(\fread($this->socket, $size));
    }

    public function write(string $string) 
	{
        yield Call::waitForWrite($this->socket);
        \fwrite($this->socket, $string);
    }

    public function close() 
	{
        @\fclose($this->socket);
    }
}
