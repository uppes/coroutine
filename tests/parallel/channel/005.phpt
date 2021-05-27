--TEST--
Check basic channel operation duplicate name
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

$channel  = Channel::make("io");

try {
    Channel::make("io");
} catch (\parallel\Channel\Error\Existence $th) {
    var_dump($th->getMessage());
}
?>
--EXPECT--
string(31) "channel named io already exists"
