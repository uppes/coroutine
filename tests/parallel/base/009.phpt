--TEST--
ZEND_DECLARE_ANON_CLASS
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new Async\Parallel\Runtime();

try {
	$parallel->run(function(){
		new class {};
	})->value();
} catch (Throwable $t) {
	var_dump($t->getMessage());
}
?>
--EXPECTF--
