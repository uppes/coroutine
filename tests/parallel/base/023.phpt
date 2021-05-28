--TEST--
bailed
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", ">=") || '\\' === \DIRECTORY_SEPARATOR) {
    die("skip php 7.4 required");
}
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime(sprintf("%s/bootstrap.inc", __DIR__));

try {
	$parallel->run(function(){
		thrower();
	});
} catch (Error $er) {
	/* can't catch here what is thrown in runtime */
}
?>
--EXPECTF--
Fatal error: Uncaught Exception:%S

#0 closure://function(){
%S
%S
#1 closure://function () use ($future, $args, $include, $transfer) {
%S
%S
%S
#2 %S
#3 {main} in %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 [internal function]: Async\Parallel->markAsFailed(Object(Async\Spawn\Future))
#4 %S
#5 %S
