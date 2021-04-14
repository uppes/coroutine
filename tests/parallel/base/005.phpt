--TEST--
Check parallel ini
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new Async\Parallel\Runtime();

ini_set("include_path", "/none_for_the_purposes_of_this_test");

$parallel->run(function() {
	if (ini_get("include_path") != "/none_for_the_purposes_of_this_test") {
	    echo "OK";
	}
})->value();
?>
--EXPECTF--
OK
