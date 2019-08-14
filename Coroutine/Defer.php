<?php

declare(strict_types = 1);

namespace Async\Coroutine;

class Defer
{
	/**
	 * @var callable
	 */
	private $callback;
	private $args;

	/**
	 * @var self[]
	 */
	private $prev = [];
	private $root = null;
	private $isLast = true;
	private $destructed = false;

	private function __construct($prev, $callback, $args)
	{
		$this->callback = $callback;
		$this->args = $args;
		if ($prev instanceof self) {
			if ($prev->root == null) {
				$prev->root = $prev;
			}

			$prev->root->prev[] = $prev;
			$this->root = $prev->root;
			foreach ($this->prev as $deferred) {
				$deferred->isLast = false;
			}
		}
	}

	/**
	 * @param Defer|null $previous defer
	 * @param callable $callback
	 * @param array $args
	 *
	 * @throws \Exception
	 */
	public static function deferring(&$previous, $callback, $args)
	{
		if (!\is_callable($callback)) {
			if (\is_string($callback) && !\function_exists($callback)) {
				throw new \Exception("function '{$callback}' not exist");
			}
			throw new \Exception("this is not callable");
		}

		$previous = new self($previous, $callback, $args);
	}

	public function __destruct()
	{
		if ($this->destructed) {
			return;
		}

		$this->destructed = true;
		\call_user_func_array($this->callback, $this->args);
		if ($this->root != null) {
			for ($i = \count($this->root->prev) - 1; $i >= 0; $i--) {
				$deferred = $this->root->prev[$i];
				$deferred->destructed = true;
				\call_user_func_array($deferred->callback, $deferred->args);
				$this->root->prev[$i] = null;
			}
		}
	}
}