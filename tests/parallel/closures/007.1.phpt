--TEST--
Check closures statics
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\{Channel, Runtime};

$runtime = new Runtime();

$channel = Channel::make("channel", Channel::Infinite);

$runtime->run(function(){
    $channel = Channel::open("channel");

    while (($closure = $channel->recv())) {
        $closure();
    }
}, 'channel');

for ($i = 0; $i<10; $i++)
$channel->send(function(){
    static $vars = [452];

    var_dump($vars);
});

$channel->send(false);
?>
--EXPECTF--
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
closure://function(){
%S
%S
%S
}:5:
array(1) {
  [0] =>
  int(452)
}
