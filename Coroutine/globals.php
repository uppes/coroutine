<?php

use Async\Coroutine\Call;
use Async\Coroutine\CoSocket;
use Async\Coroutine\CoSocketInterface;

if (! \function_exists('globalCoroutines')) {
	function async(callable $asyncFunction, ...$args) 
	{
		return yield Call::addTask(\awaitAble($asyncFunction, ...$args));
	}	

	function await(callable $awaitedFunction, ...$args) 
	{
		$value = yield from \awaitAble($awaitedFunction, ...$args);
		yield Coroutine::value($value);
	}

	function awaitAble(callable $awaitableFunction, ...$args) 
	{
		return yield $awaitableFunction(...$args);
	}	

	function asyncRemove(int $tid)
	{
		return Call::removeTask($tid); 
	}	

	function asyncId()
	{
		return Call::taskId();
	}	

	function asyncReadStream($stream)
	{
		return Call::waitForRead($stream); 
	}

	function asyncWriteStream($stream)
	{
		return Call::waitForWrite($stream);
	}	

	function secureServer(
		$uri = null, 
		array $options = [],	
		string $privatekeyFile = 'privatekey.pem', 
		string $certificateFile = 'certificate.crt', 
		string $signingFile = 'signing.csr',
		string $ssl_path = null, 
		array $details = []) : CoSocketInterface
	{
		return CoSocket::secureServer($uri, $options, $privatekeyFile, $certificateFile, $signingFile, $ssl_path, $details);
	}

	function createServer($uri = null, array $options = []): CoSocketInterface
	{
		return CoSocket::createServer($uri, $options);
	}	

	function createClient($uri = null, array $options = [], bool $isRequest = false)
	{
		return CoSocket::createClient($uri, $options, $isRequest);
	}

	function clientRead(CoSocketInterface $socket, int $size = 20240) 
	{
		return $socket->response($size);
	}

	function clientWrite(CoSocketInterface $socket, string $response = null) 
	{
		return \writeSocket($socket, $response);
	}

	function closeClient(CoSocketInterface $socket)
	{
		return \closeSocket($socket);
	}	

	function acceptSocket(CoSocketInterface $socket)
	{
		return $socket->accept();
	}	

	function readSocket(CoSocketInterface $socket, int $size = 8192)
	{
		return $socket->read($size);
	}	

	function writeSocket(CoSocketInterface $socket, string $response = null)
	{
		return $socket->write($response);
	}	

	function closeSocket(CoSocketInterface $socket)
	{
		return $socket->close();
	}	

	function remoteAddress(CoSocketInterface $socket)
	{
		return $socket->address();
	}

	function globalCoroutines()
	{
		return true;
	}	
}
