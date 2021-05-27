--TEST--
Check parallel global scope
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
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
closure://function() {
%S

%S

%S
}:5:
int(10)
bool(false)
NULL
