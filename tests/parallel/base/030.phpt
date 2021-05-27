--TEST--
parallel future exception
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", ">=")) {
    die("skip php 7.4 required");
}?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();
$future   = $parallel->run(function(){
	throw new Exception();

	return false;
});

$future->value();
?>
--EXPECTF--
Fatal error: Uncaught Exception:%S

#0 closure://function () use ($future, $args, $include, $transfer) {
%S
%S
%S
#1 %S
#2 {main} in %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 [internal function]: Async\Parallel->markAsFailed(Object(Async\Spawn\Future))
#4 %S
