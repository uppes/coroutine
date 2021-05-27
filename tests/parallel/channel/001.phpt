--TEST--
Check basic channel operation (argument)
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
use \parallel\Channel;

$channel  = Channel::make("io");

$parallel = new parallel\Runtime();
$parallel->run(function($channel){
	var_dump($channel);

}, (string) $channel);
?>
--EXPECTF--
string(2) "io"
