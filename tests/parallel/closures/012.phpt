--TEST--
Check closures cached function check
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Runtime;

$runtime = new Runtime;

$closure = function() {
    $closure = function() {
        return "OK";
    };

    var_dump($closure());
};

$runtime->run($closure);
$runtime->run($closure);
?>
--EXPECTF--
closure://function() {
%S
%S
%S

%S
%S}:7:
string(2) "OK"
closure://function() {
%S
%S
%S

%S
%S}:7:
string(2) "OK"
