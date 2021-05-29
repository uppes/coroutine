--TEST--
parallel check type list property not found
--SKIPIF--
<?php
if (!extension_loaded('parallel')) {
	echo 'skip';
}
?>
--FILE--
<?php
include 'vendor/autoload.php';

use Async\Tests\parallel\base\Foo;

\parallel\run(function(Foo $a){
        echo "OK\n";
    }, new Foo());
?>
--EXPECT--
OK
