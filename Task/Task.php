<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Task;

use Async\Task\Co;

class Task 
{
    protected $taskId;
    protected $coroutine;
    protected $sendValue = null;
    protected $beforeFirstYield = true;
    protected $exception = null;

    public function __construct($taskId, Generator $coroutine) 
	{
        $this->taskId = $taskId;
        $this->coroutine = Co::routine($coroutine);
    }

    public function getTaskId() 
	{
        return $this->taskId;
    }

    public function setSendValue($sendValue) 
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
}
