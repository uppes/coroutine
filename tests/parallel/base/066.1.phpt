--TEST--
parallel check type list property
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
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
