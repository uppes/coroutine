--TEST--
Check closures over channel unbuffered destroy last
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
$runtime = new \parallel\Runtime;
$channel = \parallel\Channel::make("channel");

$runtime->run(function(){
    $channel =
        \parallel\Channel::open("channel");
    $closure = $channel->recv();
    $closure();
    $closure = $channel->recv();
    $closure();
}, 'channel');

$channel->send(function(){
    echo "OK\n";
});

$channel->send(function(){
    echo "OK\n";
});
?>
--EXPECT--
OK
OK
