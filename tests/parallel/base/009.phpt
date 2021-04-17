--TEST--
ZEND_DECLARE_ANON_CLASS
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

try {
	$parallel->run(function(){
		new class {};
		print('No "illegal instruction (new class) on line 1 of task", all good!');
	});
} catch (Error $t) {
	var_dump($t->getMessage());
}
?>
--EXPECT--
No "illegal instruction (new class) on line 1 of task", all good!
