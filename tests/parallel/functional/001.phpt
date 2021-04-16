--TEST--
Check functional parallel operation
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

\parallel\run(function(){
	echo "OK";
});
?>
--EXPECT--
OK
