<?php

namespace Async\Coroutine\Exceptions;

use Async\Coroutine\Exceptions\RuntimeException;

class CancelledError extends RuntimeException
{
    public function __construct($msg = null)
    {
        parent::__construct(\sprintf('The operation has been cancelled, with: %s', $msg));
    }
}
