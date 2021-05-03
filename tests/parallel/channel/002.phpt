--TEST--
Check make/open/cast
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

$channel  = Channel::make("io");

$parallel = new parallel\Runtime();
$parallel->run(function($channel){
	$channel = Channel::open($channel);

	var_dump((string)$channel);

}, (string) $channel);
?>
--EXPECTF--
closure://function($channel){
%S

%S

}:5:
string(2) "io"
