--TEST--
Check closures over channel buffered
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$runtime = new \parallel\Runtime;
$channel = \parallel\Channel::make("channel", \parallel\Channel::Infinite);

$runtime->run(function(){
    $channel =
        \parallel\Channel::open("channel");
    $closure = $channel->recv();
    $closure();
}, 'channel');

$channel->send(function(){
    echo "OK\n";
});
?>
--EXPECT--
OK
