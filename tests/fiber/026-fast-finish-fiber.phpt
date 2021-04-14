--TEST--
Fast finishing fiber does not leak
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{

$fiber = new Fiber(function() { return 'test';});
var_dump($fiber->isStarted());
var_dump(yield $fiber->start());
var_dump($fiber->getReturn());
var_dump($fiber->isTerminated());
}

\coroutine_run(main());

--EXPECTF--
bool(false)
NULL
string(4) "test"
bool(true)
