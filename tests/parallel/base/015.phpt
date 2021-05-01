--TEST--
ZEND_YIELD
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();
$var     = null;

$parallel->run(function() {
		yield;
});
?>
--EXPECTF--
Fatal error: Uncaught Exception: Serialization of 'Generator' is not allowed

#0 %S
#1 %S
#2 %S
#3 {main} in %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 [internal function]: Async\Parallel->markAsFailed(Object(Async\Spawn\Future))
#4 %S
