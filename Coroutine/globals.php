<?php

use Async\Coroutine\Call;
use Async\Coroutine\CoSocket;

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
	function asyncReadStream($socket)
	{
		return Call::waitForRead($socket); 
	}	
}

if (! function_exists('asyncWriteStream')) {
	function asyncWriteStream($socket)
	{
		return Call::waitForWrite($socket);
	}	
}

if (! function_exists('asyncCreate')) {
	function asyncCreate($socket)
	{
		return new CoSocket($socket);
	}	
}

if (! function_exists('asyncAccept')) {
	function asyncAccept($socket)
	{
		return $socket->accept();
	}	
}

if (! function_exists('asyncRead')) {
	function asyncRead($socket, $size)
	{
		return $socket->read($size);
	}	
}

if (! function_exists('asyncWrite')) {
	function asyncWrite($socket, $response)
	{
		return $socket->write($response);
	}	
}

if (! function_exists('asyncClose')) {
	function asyncClose($socket)
	{
		return $socket->close();
	}	
}
