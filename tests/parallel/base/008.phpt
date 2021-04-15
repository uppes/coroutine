--TEST--
ZEND_DECLARE_CLASS
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

try {
	$parallel->run(function(){
		class Foo {}
	});
} catch (Throwable $t) {
	var_dump($t->getMessage());
}
?>
--EXPECTF--
string(%d) "syntax error, unexpected '\' (T_NS_SEPARATOR), expecting identifier (T_STRING)

#0 [internal function]: Opis\Closure\SerializableClosure->unserialize(NULL)
#1 %S
#2 [internal function]: Opis\Closure\SerializableClosure->unserialize('a:5:{s:3:"use";...')
#3 %S
#4 %S
#5 %S
#6 {main}"
