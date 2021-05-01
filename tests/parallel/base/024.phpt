--TEST--
parallel can create parallel
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new parallel\Runtime();

$parallel->run(function(){
	try {
		$child = new \parallel\Runtime();

		echo "OK\n";
	} catch (\parallel\Exception $ex) {

	}
});
?>
--EXPECT--
OK
