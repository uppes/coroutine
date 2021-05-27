--TEST--
parallel check type list missing definition
--SKIPIF--
<?php
if (!extension_loaded('parallel')) {
	echo 'skip';
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
class Foo{}

try {
    \parallel\run(function(Foo $a){
        echo "FAIL\n";
    }, 42);
} catch (parallel\Runtime\Error\IllegalParameter) {
    echo "OK\n";
}
?>
--EXPECT--
OK
