--TEST--
Check clone Channel
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

$channel = new Channel;
$clone   = clone $channel;

var_dump($clone, $channel);
?>
--EXPECTF--
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(%d) "%s[1]"
  ["index":protected]=>
  int(1)
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
  ["future":protected]=>
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
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(%d) "%s[1]"
  ["index":protected]=>
  int(1)
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
  ["future":protected]=>
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
