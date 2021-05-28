--TEST--
Check functional bootstrap error (set after run)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$sync = \parallel\Channel::make("sync");
\parallel\run(function($sync) {
    $sync->recv();
}, $sync);

try {
    \parallel\bootstrap("1.php");
} catch (\parallel\Runtime\Error\Bootstrap $ex) {
    var_dump($ex->getMessage());
}

$sync->send(true);
?>
--EXPECT--
string(76) "\parallel\bootstrap should be called once, before any calls to \parallel\run"
