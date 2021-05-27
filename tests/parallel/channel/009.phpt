--TEST--
Check channel operation close on closed
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

$channel = Channel::make("io");
$channel->close();

try {
   $channel->close();
} catch (\parallel\Channel\Error\Closed $th) {
    var_dump($th->getMessage());
}
?>
--EXPECT--
string(26) "channel(io) already closed"
