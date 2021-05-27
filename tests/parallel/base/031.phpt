--TEST--
parallel future saved
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();
$future   = $parallel->run(function(){
	return 42;
});

if ($future->value() == 42 &&
    $future->value() == 42) {
	echo "OK";
}

?>
--EXPECT--
OK
