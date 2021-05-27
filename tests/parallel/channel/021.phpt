--TEST--
Check anonymous Channel
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

var_dump(new Channel);
var_dump(new Channel(Channel::Infinite));
var_dump(new Channel(1));

$create = function() {
    return new Channel;
};

var_dump((string) $create(), (string)$create());

function create() {
    return new Channel;
}

var_dump((string) create(), (string) create());

class Create {
    public static function channel() {
        return new Channel;
    }
}

var_dump((string) Create::channel(), (string) Create::channel());
?>
--EXPECTF--
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(%d) "%s[%d]"
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
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(%d) "%s[%d]"
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
object(parallel\Channel)#%d (%d) {
  ["name":protected]=>
  string(%d) "%s[%d]"
  ["index":protected]=>
  int(3)
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
string(%d) "%s[4]"
string(%d) "%s[5]"
string(%d) "%s[6]"
string(%d) "%s[7]"
string(%d) "%s[8]"
string(%d) "%s[9]"
