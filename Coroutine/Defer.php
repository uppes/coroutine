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
	private $errorCatcher = null;
	private $recoverArgs;
	private $recover;

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

	public static function recover(&$previous, callable $callback, ...$args)
	{
        $previous->recover = $callback;
		$previous->recoverArgs = $args;
	}

	public function __destruct()
	{
        if ($this->destructed) {
            return;
        }

        $this->destructed = true;
        try {
            \call_user_func_array($this->callback, $this->args);
        } catch (\Exception $e) {
            if ($this->recover !== null) {
                $this->errorCatcher = null;
                \call_user_func_array($this->recover, $this->recoverArgs);
            } else
                $this->errorCatcher = $e;
                
        }

        $this->recover = null;
        $this->recoverArgs = [];
        if ($this->root != null) {
            for ($i = \count($this->root->prev) - 1; $i >= 0; $i--) {
                $deferred = $this->root->prev[$i];
                $deferred->destructed = true;
                try {
                    \call_user_func_array($deferred->callback, $deferred->args);
                } catch (\Exception $e) {
                    if ($deferred->recover !== null) {
                        $this->errorCatcher = null;
                        \call_user_func_array($deferred->recover, $deferred->recoverArgs);
                    } else
                        $this->errorCatcher = $e;
                }
                $deferred->recover = null;
                $deferred->recoverArgs = null;
                $this->root->prev[$i] = null;
            }
        }

        if ($this->errorCatcher !== null) {
            $error = $this->errorCatcher;
            $this->errorCatcher = null;
            throw $error;
        }
	}
}