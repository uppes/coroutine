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
    protected $status;
    protected $result;
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

    public function isFinished(): bool
	{
        return !$this->coroutine->valid();
    }

    public function setStatus(string $status)
	{
        $this->status = $status;
    }

    public function setResult($value)
	{
        $this->result = $value;
    }
    
    public function cancel()
    {
    }

    public function cancelled(): bool
    {
        return ($this->status == 'terminated');
    }

    public function done(): bool
    {
        return ($this->status == 'completed');
    }

    public function result()
    {
        if ($this->done() && !empty($this->result))
            return $this->result;
        else
            throw new \Exception("Invalid State Error");            
    }
}
