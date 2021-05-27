--TEST--
Check closures binding static
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", ">=")) {
    die("skip php 7.4 required");
}?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\{Runtime, Channel};

include sprintf("%s/011.inc", __DIR__);

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
string(3) "Foo"
