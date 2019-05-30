<?php

namespace Async\Coroutine\Exceptions;

use RuntimeException;

class TimeoutError extends RuntimeException
{
    public function __construct(float $time = null)
    {
        parent::__construct(\sprintf('The operation has exceeded the given deadline: %f', (float) $time));
    }
}
