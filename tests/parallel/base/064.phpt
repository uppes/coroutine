--TEST--
parallel check type list simple
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
class Foo{}
class Bar{}

\parallel\run(function(int $a){
	echo "OK\n";
}, 42);
?>
--EXPECT--
OK
