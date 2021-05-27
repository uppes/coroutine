--TEST--
Check basic channel operation non-existent name
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

try {
    Channel::open("io");
} catch (\parallel\Channel\Error\Existence $th) {
    var_dump($th->getMessage());
}
?>
--EXPECT--
string(26) "channel named io not found"
