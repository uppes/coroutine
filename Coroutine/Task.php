<?php

namespace Async\Coroutine;

use Async\Coroutine\Coroutine;
use Async\Coroutine\TaskInterface;

/**
 * Task is used to schedule coroutines concurrently.
 * When a coroutine is wrapped into a Task with functions like Coroutine::createTask() 
 * the coroutine is automatically scheduled to run soon.
 * 
 */
class Task implements TaskInterface
{	
    protected $taskId;
    protected $coroutine;
    protected $state = null;
    protected $result;
    protected $error;
    protected $sendValue = null;
    protected $beforeFirstYield = true;
    protected $exception = null;

    public function __construct($taskId, \Generator $coroutine) 
	{
        $this->taskId = $taskId;
        $this->state = 'pending';
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
            $this->error = $value;
            return $value;
        } else {
            $value = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            $this->result = $value;
            return $value;
        }
    }

    public function isFinished(): bool
	{
        return !$this->coroutine->valid();
    }

    public function setState(string $status)
	{
        $this->state = $status;
    }

    public function setResult($value)
	{
        $this->result = $value;
    }
    
    public function cancelled(): bool
    {
        return ($this->state == 'terminated');
    }

    public function done(): bool
    {
        return ($this->state == 'completed');
    }

    public function result()
    {
        if ($this->done() && !empty($this->result))
            return $this->result;
        elseif ($this->cancelled())
            throw new \Exception("Cancelled Error");            
        elseif (!$this->done() && empty($this->result))
            throw new \Exception("Invalid State Error");
        else
          throw $this->error;
    } 
}
