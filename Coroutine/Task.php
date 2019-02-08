<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Coroutine;

use Async\Coroutine\Co;
use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\TaskInterface;

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
        $this->coroutine = Co::routine($coroutine);
    }

    public function taskId() 
	{
        return $this->taskId;
    }

    public function sendValue($sendValue) 
	{
        $this->sendValue = $sendValue;
    }

    public function setException($exception) 
	{
        $this->exception = $exception;
    }

    public function run() 
	{
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } elseif ($this->exception) {
            $retval = $this->coroutine->throw($this->exception);
            $this->exception = null;
            return $retval;
        } else {
            $retval = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $retval;
        }
    }

    public function isFinished() 
	{
        return !$this->coroutine->valid();
    }
	
    public function isCancelled() 
	{
	}
	
    public function isComplete()
	{
	}
	
    public function isSuccessful() 
	{
	}
	
    public function isFaulted()
	{
	}

    public function cancel() 
	{
	}
		
    public function getResult() 
	{
	}
		
    public function getException()
	{
	}
		
    public function tick(SchedulerInterface $scheduler)
	{
	}	
		
    protected function doTick(SchedulerInterface $scheduler)
	{
	}
}
