--TEST--
Check Channel closures inside object properties
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\{Runtime, Channel};

$runtime = new Runtime;
$channel = Channel::make("channel");

$runtime->run(function($channel){
    $data = $channel->recv();

    ($data->closure)();
}, $channel);

$std = new stdClass;
$std->closure = function(){
    echo "OK";
};

$channel->send($std);
?>
--EXPECT--
OK
