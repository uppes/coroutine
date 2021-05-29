--TEST--
parallel class check invalid member
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", ">=")) {
    die("skip php 7.4 required");
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
include sprintf("%s/060.inc", __DIR__);

$parallel = new \parallel\Runtime(sprintf("%s/060.inc", __DIR__));

$foo = new Foo();
$foo->date = new DateTime;

try {
    $parallel->run(function(Foo $foo){
    }, $foo);
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(%d) "illegal parameter"
