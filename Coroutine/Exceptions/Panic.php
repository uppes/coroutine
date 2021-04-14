<?php

namespace Async\Exceptions;

use Async\Exceptions\Panicking;

class Panic extends \Exception implements Panicking
{
    public function __construct($message = null, $code = 0, \Throwable $previous = null)
    {
        parent::__construct(\sprintf('Coroutine task has erred: %s', $message), $code, $previous);
    }
}
