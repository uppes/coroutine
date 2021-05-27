--TEST--
Check Channel share
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\{Runtime, Channel};

$runtime = new Runtime;

$future = $runtime->run(function($channel){
    return $channel;
}, Channel::make("channel"));

var_dump($future->value());
?>
--EXPECTF--
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(7) "channel"
  ["index":protected]=>
  int(%d)
  ["capacity":protected]=>
  int(-1)
  ["type":protected]=>
  string(8) "buffered"
  ["buffered":protected]=>
  object(SplQueue)#%d (2) {
    ["flags":"SplDoublyLinkedList":private]=>
    int(4)
    ["dllist":"SplDoublyLinkedList":private]=>
    array(0) {
    }
  }
  ["whenDrained":"Async\Spawn\Channeled":private]=>
%S
  ["input":"Async\Spawn\Channeled":private]=>
  array(0) {
  }
  ["open":protected]=>
%S
  ["state":protected]=>
%S
  ["channel":protected]=>
%S
  ["process":protected]=>
%S
  ["paralleling":protected]=>
%S
  ["futureInput":protected]=>
%S
  ["futureOutput":protected]=>
%S
  ["futureError":protected]=>
%S
}
