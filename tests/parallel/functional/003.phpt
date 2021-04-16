--TEST--
Check functional future
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
\parallel\bootstrap(
    sprintf("%s/003.inc", __DIR__));

$future = \parallel\run(function(){
	return foo();
});

var_dump($future->value());
?>
--EXPECT--
OKstring(2) "OK"
