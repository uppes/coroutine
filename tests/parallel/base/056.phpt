--TEST--
parallel object check finds illegal property in table
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

$std = new stdClass;
$std->date = new DateTime;

try {
    $parallel->run(function($std){
    }, $std);
} catch (parallel\Runtime\Error\IllegalParameter $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECT--
