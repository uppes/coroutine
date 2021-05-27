--TEST--
parallel check scope changed in task
--SKIPIF--
<?php
if (!extension_loaded('parallel')) {
	echo 'skip';
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
include sprintf("%s/053.inc", __DIR__);

$parallel =
    new \parallel\Runtime(sprintf("%s/053.inc", __DIR__));

$foo = new Foo();

$parallel->run(
    Closure::fromCallable([$foo, "run"]));
?>
--EXPECT--
string(3) "Foo"
