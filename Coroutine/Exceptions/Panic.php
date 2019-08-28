<?php

namespace Async\Coroutine\Exceptions;

use Async\Coroutine\Exceptions\PanicInterface;

class Panic extends \Exception implements PanicInterface
{
    public function __construct($message = null, $code = 0, \Throwable $previous = null)
    {
        parent::__construct(\sprintf('Coroutine task has erred: %s', $message), $code, $previous);
    }
}
