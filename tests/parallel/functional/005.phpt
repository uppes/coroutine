--TEST--
Check functional bootstrap error (previously set)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
\parallel\bootstrap("1.php");

try {
    \parallel\bootstrap("2.php");
} catch (\parallel\Runtime\Error\Bootstrap $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECT--
string(40) "\parallel\bootstrap already set to 1.php"
