--TEST--
parallel Future value refcounted unfetched
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$future = $parallel->run(function(){
	return [42];
});

$parallel->close();

/* this test will leak if dtor is incorrect, cannot fetch future value */
echo "OK";
?>
--EXPECT--
OK
