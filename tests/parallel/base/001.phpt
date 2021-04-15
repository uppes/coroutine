--TEST--
Check basic parallel operation
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

$parallel->run(function(){
	echo "OK";
});
?>
--EXPECT--
OK
