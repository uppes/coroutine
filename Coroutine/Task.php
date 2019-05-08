<?php

namespace Async\Coroutine;

use Async\Coroutine\Coroutine;
use Async\Coroutine\TaskInterface;

/**
 * Task is used to schedule coroutines concurrently.
 */
class Task implements TaskInterface
{	
    protected $taskId;
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;
    protected $exception = null;

    public function __construct($taskId, \Generator $coroutine) 
	{
        $this->taskId = $taskId;
        $this->coroutine = Coroutine::create($coroutine);
    }

    public function taskId(): int 
	{
        return $this->taskId;
    }

    public function sendValue($sendValue) 
	{
        $this->sendValue = $sendValue;
    }

    public function exception($exception) 
	{
        $this->exception = $exception;
    }

    public function run() 
	{
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } elseif ($this->exception) {
            $value = $this->coroutine->throw($this->exception);
            $this->exception = null;
            return $value;
        } else {
            $value = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $value;
        }
    }

    public function isFinished() 
	{
        return !$this->coroutine->valid();
    }
    
    public function cancel()
    {
    }

    public function cancelled()
    {
    }

    public function done() 
    {
    }


    public function result()
    {
    }
}
