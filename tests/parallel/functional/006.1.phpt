--TEST--
Check functional bootstrap error (set after run)
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
$sync = \parallel\Channel::make("sync");
\parallel\run(function($sync) {
    $sync->recv();
}, $sync);

try {
    \parallel\bootstrap("1.php");
} catch (\parallel\Runtime\Error\Bootstrap $ex) {
    var_dump($ex->getMessage());
}

$sync->send(true);
?>
--EXPECT--
string(76) "\parallel\bootstrap should be called once, before any calls to \parallel\run"
