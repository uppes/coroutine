--TEST--
parallel Future exception with trace
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
$parallel = new \parallel\Runtime(sprintf("%s/bootstrap.inc", __DIR__));

$future = $parallel->run(function(){
	$foo = new Foo();

	return $foo->bar([42],new stdClass);
});

var_dump($future->value());
?>
--EXPECTF--
Fatal error: Uncaught RuntimeException: message

#0 %S
#1 closure://function(){
%S

%S
}(5): Foo->bar(%S
#2 closure://function () use ($future, $args, $include, $transfer) {
%S
%S
%S
#3 %S
#4 {main} in %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 %S
