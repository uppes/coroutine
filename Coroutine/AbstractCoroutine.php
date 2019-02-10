<?php

namespace Async\Coroutine;

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
