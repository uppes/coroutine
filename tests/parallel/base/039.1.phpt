--TEST--
parallel Future done
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();
$sync  = \parallel\Channel::make("sync");

$future = $parallel->run(function(){
    $sync = \parallel\Channel::open("sync");

    $sync->recv();

	return [42];
}, 'sync');

var_dump($future->done());

$sync->send(true);

$parallel->close();

var_dump($future->done());
?>
--EXPECT--
bool(false)
bool(true)
