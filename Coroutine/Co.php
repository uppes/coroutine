<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Coroutine;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AllTask;
use Async\Coroutine\Tasks\AnyTask;
use Async\Coroutine\Tasks\CallableTask;
use Async\Coroutine\Tasks\DelayedTask;
use Async\Coroutine\Tasks\GeneratorTask;
use Async\Coroutine\Tasks\SomeTask;
use Async\Coroutine\Tasks\TaskInterface;
use Async\Coroutine\Tasks\PromiseTask;
use Async\Coroutine\Tasks\ThrottledTask;

abstract class AbstractCoroutine 
{
    protected $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }
}

class CoroutineReturnValue extends AbstractCoroutine 
{ 
}

class CoroutinePlainValue extends AbstractCoroutine
{ 
}

class Co 
{
	private function __construct() 
	{		
	}
	
	public static function value($value) 
	{
		return new CoroutineReturnValue($value);
	}

	public static function plain($value) 
	{
		return new CoroutinePlainValue($value);
	}

	public static function routine(\Generator $gen) 
	{
		$stack = new \SplStack;
		$exception = null;

		for (;;) {
			try {
				if ($exception) {
					$gen->throw($exception);
					$exception = null;
					continue;
				}

				$value = $gen->current();

				if ($value instanceof \Generator) {
					$stack->push($gen);
					$gen = $value;
					continue;
				}

				$isReturnValue = $value instanceof CoroutineReturnValue;
				if (!$gen->valid() || $isReturnValue) {
					if ($stack->isEmpty()) {
						return;
					}

					$gen = $stack->pop();
					$gen->send($isReturnValue ? $value->getValue() : NULL);
					continue;
				}

				if ($value instanceof CoroutinePlainValue) {
					$value = $value->getValue();
				}

				try {
					$sendValue = (yield $gen->key() => $value);
				} catch (\Exception $e) {
					$gen->throw($e);
					continue;
				}

				$gen->send($sendValue);
			} catch (\Exception $e) {
				if ($stack->isEmpty()) {
					throw $e;
				}

				$gen = $stack->pop();
				$exception = $e;
			}
		}
	}	
	
    /**
     * Takes an object and returns an appropriate task object:
     * 
     *   - If $object is a TaskInterface, it is returned
     *   - If $object is a promise, a PromiseTask is returned
     *   - If $object is a generator, a GeneratorTask is returned
     *   - If $object is callable, a CallableTask is returned that will call the
     *     object only once
     *   - If $object is anything else, a task is returned whose result will be
     *     $object
     * 
     * This method is provided as a convenience for the most commonly used tasks
     * Other tasks can be created directly using their constructors
     * 
     * @param mixed $object
     * @return TaskInterface
     */
    public static function task($object) 
	{
        if ( $object instanceof TaskInterface ) {
            return $object;
		} elseif ( $object instanceof PromiseInterface ) {
            return new PromiseTask($object);
        } elseif ( $object instanceof \Generator ) {
            return new GeneratorTask($object);        
        } elseif ( is_callable($object) ) {
            return new CallableTask($object);
        }
		
        return new CallableTask(function() use($object) { return $object; });
    }
    
    /**
     * Shorthand for creating a SomeTask
     * 
     * @param Task[] $tasks
     * @param integer $howMany
     * @return SomeTask
     * 
     * @codeCoverageIgnore
     */
    public static function some(array $tasks, $howMany) 
	{
        return new SomeTask($tasks, $howMany);
    }
    
    /**
     * Shorthand for creating an AllTask
     * 
     * @param Task[] $tasks
     * @return AllTask
     * 
     * @codeCoverageIgnore
     */
    public static function all(array $tasks) 
	{
        return new AllTask($tasks);
    }
    
    /**
     * Shorthand for creating an AnyTask
     * 
     * @param Task[] $tasks
     * @return AnyTask
     * 
     * @codeCoverageIgnore
     */
    public static function any(array $tasks) 
	{
        return new AnyTask($tasks);
    }
    
    /**
     * Co::async is called on the given object to get a task. A new task is
     * returned that delays the start of that task.
     * 
     * @param mixed $object
     * @param float $delay
     * @return DelayedTask
     * 
     * @codeCoverageIgnore
     */
    public static function delay($object, $delay) 
	{
        return new DelayedTask(Co::async($object), $delay);
    }
    
    /**
     * Co::async is called on the given object to get a task. A new task is
     * returned that throttles calls to the tick method of that task.
     * 
     * @param mixed $object
     * @param float $tickInterval
     * @return ThrottledTask
     * 
     * @codeCoverageIgnore
     */
    public static function throttle($object, $tickInterval) 
	{
        return new ThrottledTask(Co::async($object), $tickInterval);
    }
}
