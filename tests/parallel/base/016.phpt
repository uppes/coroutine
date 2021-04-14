--TEST--
ZEND_YIELD_FROM
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new Async\Parallel\Runtime();
$var     = null;

$parallel->run(function() {
	yield from [];
})->value();
?>
--EXPECTF--
Fatal error: Uncaught Exception: Serialization of 'Generator' is not allowed

#0 %S
#1 %S
#2 {main} in %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 [internal function]: Async\Parallel->markAsFailed(Object(Async\Spawn\Launcher))
#4 %S
