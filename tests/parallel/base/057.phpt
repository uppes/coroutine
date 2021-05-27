--TEST--
parallel object check finds illegal property inline
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip";
if (ini_get("opcache.enable_cli")) {
    die("skip opcache must not be loaded");
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime;

class Foo {
    public $property;
}

$foo = new Foo();
$foo->property = new DateTime;

try {
    $parallel->run(function($foo){
    }, $foo);
} catch (parallel\Runtime\Error\IllegalParameter $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECT--
