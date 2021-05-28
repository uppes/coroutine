--TEST--
Check functional bootstrap error (set in task)
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", ">=")) {
    die("skip php 7.4 required");
}?>
--FILE--
<?php
include 'vendor/autoload.php';
$future = \parallel\run(function() {
    \parallel\bootstrap("1.php");
});

try {
    $future->value();
} catch (\parallel\Runtime\Error\Bootstrap $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(%S) "\parallel\bootstrap should be called once, before any calls to \parallel\run

#0 closure://function() {
%S
%S
#1 closure://function () use ($future, $args, $include, $transfer) {
%S
%S
%S
#2 %S
#3 {main}"
