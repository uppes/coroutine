--TEST--
parallel future saved null
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();
$future   = $parallel->run(function(){
	return null;
});

if ($future->value() == null &&
    $future->value() == null) {
	echo "OK";
}

?>
--EXPECT--
OK
