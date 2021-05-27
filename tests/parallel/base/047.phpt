--TEST--
parallel cancellation (ready)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$future = $parallel->run(function(){});

$future->value();

var_dump($future->cancel(), $future->cancelled());
?>
--EXPECT--
bool(false)
bool(false)
