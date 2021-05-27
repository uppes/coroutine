--TEST--
Check Channel debug
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

var_dump(Channel::make("unbuffered"));

var_dump(Channel::make("buffered", 1));

var_dump(Channel::make("infinite", Channel::Infinite));

$channel = Channel::make("contents", 1);
$channel->send(1);

var_dump($channel);
?>
--EXPECTF--
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(10) "unbuffered"
  ["index":protected]=>
  int(0)
  ["capacity":protected]=>
  int(-1)
  ["type":protected]=>
  string(8) "buffered"
  ["buffered":protected]=>
  object(SplQueue)#2 (2) {
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
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(8) "buffered"
  ["index":protected]=>
  int(0)
  ["capacity":protected]=>
  int(1)
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
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(%d) "infinite"
  ["index":protected]=>
  int(0)
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
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(8) "contents"
  ["index":protected]=>
  int(0)
  ["capacity":protected]=>
  int(1)
  ["type":protected]=>
  string(8) "buffered"
  ["buffered":protected]=>
  object(SplQueue)#%d (2) {
    ["flags":"SplDoublyLinkedList":private]=>
    int(4)
    ["dllist":"SplDoublyLinkedList":private]=>
    array(1) {
      [0]=>
      string(8) "aToxOw=="
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
