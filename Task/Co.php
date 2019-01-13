<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Task;

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
	
	public static function retval($value) 
	{
		return new CoroutineReturnValue($value);
	}

	public static function plainval($value) 
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
}
