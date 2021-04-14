--TEST--
Check parallel return values
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new Async\Parallel\Runtime();

$future = $parallel->run(function() {
	return 10;
});

var_dump($future->value());
?>
--EXPECT--
int(10)
