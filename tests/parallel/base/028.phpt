--TEST--
parallel bootstrap
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime(sprintf("%s/bootstrap.inc", __DIR__));

$future = $parallel->run(function(){
	return bootstrapped();
});

var_dump($future->value());
?>
--EXPECT--
bool(true)
