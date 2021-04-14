--TEST--
Arguments to fiber callback
--SKIPIF--
<?php if (!((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{

$fiber = new Fiber(function (int $x) {
    return $x + yield Fiber::suspend($x);
});

$x = yield $fiber->start(1);
yield $fiber->resume(0);
var_dump($fiber->getReturn());

$fiber = new Fiber(function (int $x) {
    return $x + yield Fiber::suspend($x);
});

yield $fiber->start('test');

}

\coroutine_run(main());

--EXPECTF--
int(1)

Fatal error: Uncaught TypeError: {closure}(): Argument #1 ($x) must be of type int, string given, called in %S
Stack trace:
#0 %S
#1 [internal function]: awaitable(%S
#2 %S
#3 [internal function]: Async\Coroutine::create(%S
#4 %S
#5 %S
#6 %S
#7 %S
#8 %S
#9 %S
#10 {main}
  thrown in %S
