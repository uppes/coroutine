--TEST--
Test throwing into fiber
--SKIPIF--
<?php include __DIR__ . '/include/skip-if.php';
--FILE--
<?php

$fiber = new Fiber(function (): void {
    Fiber::suspend('test');
});

$value = $fiber->start();
var_dump($value);

$fiber->throw(new Exception('test'));

--EXPECTF--
string(4) "test"

Fatal error: Uncaught Exception: test in %s002-throw.php:%d
Stack trace:
#0 {main}
  thrown in %s002-throw.php on line %d
