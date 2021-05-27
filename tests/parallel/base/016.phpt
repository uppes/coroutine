--TEST--
ZEND_YIELD_FROM
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();
$var     = null;

try {
	$parallel->run(function() {
		yield from [];
	});
} catch (\parallel\Runtime\Error\IllegalInstruction $ex) {
	var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(%d) "Serialization of 'Generator' is not allowed

#0 %S
#1 %S
#2 %S
#3 {main}"
