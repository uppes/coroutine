--TEST--
Check functional bootstrap
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
\parallel\bootstrap(
    sprintf("%s/002.inc", __DIR__));
\parallel\run(function(){
	foo();
});
?>
--EXPECT--
OK
