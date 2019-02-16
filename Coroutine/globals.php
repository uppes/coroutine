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
	function createSocket($uri = 8000): CoSocketInterface
	{
		// a single port has been given => assume localhost
        if ((string)(int)$uri === (string)$uri) {
            $uri = '127.0.0.1:' . $uri;
		}
		
        // assume default scheme if none has been given
        if (\strpos($uri, '://') === false) {
            $uri = 'tcp://' . $uri;
		}
		
        // parse_url() does not accept null ports (random port assignment) => manually remove
        if (\substr($uri, -2) === ':0') {
            $parts = \parse_url(\substr($uri, 0, -2));
            if ($parts) {
                $parts['port'] = 0;
            }
        } else {
            $parts = \parse_url($uri);
		}
		
        // ensure URI contains TCP scheme, host and port
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['port']) || $parts['scheme'] !== 'tcp') {
            throw new \InvalidArgumentException('Invalid URI "' . $uri . '" given');
		}
		
        if (false === \filter_var(\trim($parts['host'], '[]'), \FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Given URI "' . $uri . '" does not contain a valid host IP');
        }

		$socket = @\stream_socket_server($uri, $errNo, $errStr);
		if (!$socket)
			throw new \RuntimeException('Failed to listen on "' . $uri . '": ' . $errStr, $errNo);
	
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

if (! function_exists('asyncContents')) {
	function asyncContents(string $fileUrl)
	{
		$ret = "";

		$handle = \fopen($fileUrl, 'r');

		\stream_set_blocking($handle, 0);

		while (!feof($handle)) {
			$ret .= \stream_get_contents($handle, 1);
			yield;
		}

		\fclose($handle);

		return $ret;
	}
}
