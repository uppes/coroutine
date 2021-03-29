--TEST--
ReflectionFiber basic tests
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Coroutine\Fiber;
use Async\Coroutine\ReflectionFiber;

function main()
{

$fiber = new Fiber(function () {
    $fiber = Fiber::this();
    var_dump($fiber->isStarted());
    var_dump($fiber->isRunning());
    var_dump($fiber->isSuspended());
    var_dump($fiber->isTerminated());
    yield Fiber::suspend();
});

$reflection = new ReflectionFiber($fiber);

var_dump($fiber === $reflection->getFiber());

var_dump($reflection->isStarted());
var_dump($reflection->isRunning());
var_dump($reflection->isSuspended());
var_dump($reflection->isTerminated());

yield $fiber->start();

var_dump($reflection->isStarted());
var_dump($reflection->isRunning());
var_dump($reflection->isSuspended());
var_dump($reflection->isTerminated());

var_dump($reflection->getExecutingFile());
var_dump($reflection->getExecutingLine());
var_dump($reflection->getTrace());

yield $fiber->resume();

var_dump($fiber->isStarted());
var_dump($fiber->isRunning());
var_dump($fiber->isSuspended());
var_dump($fiber->isTerminated());

}

\coroutine_run(main());

--EXPECTF--
bool(true)
bool(false)
bool(false)
bool(false)
bool(false)
bool(true)
bool(true)
bool(false)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
string(%d) "%S
int(%d)
array(1) {
  [0]=>
  string(1100) "Async\Coroutine\Fiber Object
(
    [taskFiber:protected] => Async\Coroutine\Task Object
        (
            [taskId:protected] => 1
            [daemon:protected] =>%S
            [cycles:protected] => 2
            [coroutine:protected] => Generator Object
                (
                )

            [state:protected] => running
            [result:protected] =>%S
            [sendValue:protected] =>%S
            [beforeFirstYield:protected] =>%S
            [error:protected] =>%S
            [exception:protected] =>%S
            [customState:protected] =>%S
            [customData:protected] =>%S
            [taskType:protected] => awaited
        )

    [taskType:protected] => fiber
    [cycles:protected] => 1
    [fiberId:protected] => 3
    [coroutine:protected] => Generator Object
        (
        )

    [state:protected] => suspended
    [result:protected] =>%S
    [finishResult:protected] =>%S
    [sendValue:protected] =>%S
    [fiberStarted:protected] => 1
    [error:protected] =>%S
    [exception:protected] =>%S
    [callback:protected] => Closure Object
        (
        )

)
"
}
bool(true)
bool(false)
bool(false)
bool(true)
