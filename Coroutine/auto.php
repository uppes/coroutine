<?php

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;

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
		$tid = (yield Call::taskId());        
		return yield $awaitableFunction($tid, $args);
	}	
}

if (! function_exists('asyncRemove')) {
	function asyncRemove(int $tid) 
	{
		return Call::removeTask($tid); 
	}	
}
