--TEST--
Check basic channel operation (argument)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
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
closure://function($channel){
%S

}:3:
string(2) "io"
