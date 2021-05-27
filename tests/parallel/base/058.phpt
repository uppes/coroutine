--TEST--
parallel object check finds non-existent class
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

try {
    $parallel->run(function(DoesNotExist $d){

    });
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(%d) "illegal parameter"
