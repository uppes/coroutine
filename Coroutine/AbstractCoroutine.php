<?php

declare(strict_types=1);

namespace Async\Coroutine;

abstract class AbstractCoroutine
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        $value = $this->value;
        $this->value = null;
        return $value;
    }
}
