--TEST--
Check closures binding
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\{Runtime, Channel};

include sprintf("%s/010.inc", __DIR__);

$runtime = new Runtime(sprintf("%s/010.inc", __DIR__));
$channel = Channel::make("channel");

$future = $runtime->run(function($channel){
    $closure =
        $channel->recv();
    var_dump($closure());
}, $channel);

$foo = new Foo();

$channel->send($foo->getClosure());
?>
--EXPECTF--
closure://function($channel){
%S
%S
%S
}:5:
class Foo#%d (%d) {
}
