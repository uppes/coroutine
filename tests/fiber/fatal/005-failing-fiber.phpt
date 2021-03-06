--TEST--
Test throwing into fiber
--SKIPIF--
<?php if (!((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{
$fiber = new Fiber(function () {
    yield Fiber::suspend('test');
    throw new Exception('test');
});

$value = yield $fiber->start();
var_dump($value);

yield $fiber->resume($value);
}

\coroutine_run(main());

--EXPECTF--
string(4) "test"

Fatal error: Uncaught Exception: test in %S
Stack trace:
#0 [internal function]: {closure}()
#1 %S
#2 [internal function]: Async\Coroutine::create(%S
#3 %S
#4 %S
#5 %S
#6 %S
#7 %S
#8 %S
#9 {main}
  thrown in %S
