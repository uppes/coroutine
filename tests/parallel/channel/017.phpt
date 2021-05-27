--TEST--
Check Channel closures inside arrays
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

    ($data["closure"])();
}, $channel);

$channel->send([
    "closure" => function(){
        echo "OK";
    }
]);
?>
--EXPECT--
OK
