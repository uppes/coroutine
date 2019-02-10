<?php

use Async\Coroutine\Call;

if (! function_exists('async')) {
	function async(callable $asyncFunction) 
	{
		return yield Call::addTask(awaitAble($asyncFunction));
	}	
}

if (! function_exists('await')) {
	function await(callable $awaitedFunction) 
	{     
		return async($awaitedFunction);
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
