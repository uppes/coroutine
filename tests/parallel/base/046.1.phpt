--TEST--
parallel cancellation (running)
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$sync     = \parallel\Channel::make("sync");
$future = $parallel->run(function(){
    $sync = \parallel\Channel::open("sync");
    $sync->recv();

    while(1){
        echo "waiting...\n";
        usleep(10000);
    }
}, 'sync');

$_sync     = \parallel\Channel::make("_sync");
$parallel->run(function(){
    $sync = \parallel\Channel::open("_sync");
    $sync->recv();

    echo "OK\n";
}, '_sync');

$sync->send(true);

var_dump($future->cancel(), $future->cancelled());

$_sync->send(true);
?>
--EXPECTF--
waiting...
bool(true)
bool(true)
--XLEAK--
The interrupt we use for cancellation is not treated in a thread safe way in core
