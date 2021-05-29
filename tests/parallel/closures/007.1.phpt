--TEST--
Check closures statics
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", "<")) {
    die("skip on php 7.4");
}
?>
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
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
array(1) {
  [0]=>
  int(452)
}
