<?php

namespace Async\Tests;

class MyClass
{
    public $property = null;

    public function throwException()
    {
        throw new \Exception('test');
    }
}
