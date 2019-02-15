<?php

use Async\Coroutine\Call;
use Async\Coroutine\CoSocket;
use Async\Coroutine\CoSocketInterface;

if (! function_exists('async')) {
	function async(callable $asyncFunction, $args = null) 
	{
		return yield Call::addTask(awaitAble($asyncFunction, $args));
	}	
}

if (! function_exists('await')) {
	function await(callable $awaitedFunction, $args = null) 
	{     
		return async($awaitedFunction, $args = null);
	}
}

if (! function_exists('awaitAble')) {
	function awaitAble(callable $awaitableFunction, $args = null) 
	{
		return yield $awaitableFunction($args);
	}	
}

if (! function_exists('asyncRemove')) {
	function asyncRemove(int $tid)
	{
		return Call::removeTask($tid); 
	}	
}

if (! function_exists('asyncId')) {
	function asyncId()
	{
		return Call::taskId();
	}	
}

if (! function_exists('asyncReadStream')) {
	function asyncReadStream($stream)
	{
		return Call::waitForRead($stream); 
	}	
}

if (! function_exists('asyncWriteStream')) {
	function asyncWriteStream($stream)
	{
		return Call::waitForWrite($stream);
	}	
}

if (! function_exists('createSocket')) {
	function createSocket(int $port = 80000, $uri = "tcp://localhost:"): CoSocketInterface
	{
		$socket = @\stream_socket_server($uri.$port, $errNo, $errStr);
		if (!$socket)
			throw new \Exception($errStr, $errNo);
	
		\stream_set_blocking($socket, 0);

		return new CoSocket($socket);
	}	
}

if (! function_exists('acceptSocket')) {
	function acceptSocket(CoSocketInterface $socket)
	{
		return $socket->accept();
	}	
}

if (! function_exists('readSocket')) {
	function readSocket(CoSocketInterface $socket, int $size)
	{
		return $socket->read($size);
	}	
}

if (! function_exists('writeSocket')) {
	function writeSocket(CoSocketInterface $socket, string $response)
	{
		return $socket->write($response);
	}	
}

if (! function_exists('closeSocket')) {
	function closeSocket(CoSocketInterface $socket)
	{
		return $socket->close();
	}	
}
