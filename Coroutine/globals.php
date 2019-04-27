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

if (! function_exists('createSecureSocket')) {
	function createSecureSocket(
		$uri = null, 
		array $options = [],	
		string $privatekeyFile = 'privatekey.pem', 
		string $certificateFile = 'certificate.crt', 
		string $signingFile = 'signing.csr',
		string $ssl_path = null, 
		array $details = ["commonName" => "localhost"]) : CoSocketInterface
	{
		return CoSocket::secure($uri, $options, $privatekeyFile, $certificateFile, $signingFile, $ssl_path, $details);
	}
}

if (! function_exists('createSocket')) {
	function createSocket($uri = null): CoSocketInterface
	{
		return CoSocket::create($uri);
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

if (! function_exists('socketAddress')) {
	function remoteAddress(CoSocketInterface $socket)
	{
		return $socket->address();
	}	
}

if (! function_exists('get_contents')) {
	function get_contents(string $fileUrl, float $timeout_seconds = 0.5)
	{
		$ret = "";

		$handle = \fopen($fileUrl, 'r');

		\stream_set_blocking($handle, 0);

		while (true) {			
			$startTime = \microtime(true);
			$new = \stream_get_contents($handle, 1);
			$endTime = \microtime(true);
			if (\is_string($new) && \strlen($new) >= 1) {
				$ret .= $new;
			}
			$time_used = $endTime - $startTime;
			if (($time_used >= $timeout_seconds) || ! \is_string($new) ||
					 (\is_string($new) && \strlen($new) < 1)) {
				break;
			}        
			yield;
		}

		\fclose($handle);

		return $ret;
	}
}
