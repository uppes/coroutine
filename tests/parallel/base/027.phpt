--TEST--
Future may not be constructed
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
try {
    new \parallel\Future();
} catch (\parallel\Future\Error $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECT--
string(45) "construction of Future objects is not allowed"
