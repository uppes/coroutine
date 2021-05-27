--TEST--
Check parallel global scope
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

$parallel = new parallel\Runtime();

$parallel->run(function() {
	global $thing;

	$thing = 10;
});

$future = $parallel->run(function() {
	global $thing;

	var_dump($thing);

	return false;
});

var_dump($future->value(), @$thing);
?>
--EXPECTF--
int(10)
bool(false)
NULL
