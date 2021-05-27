--TEST--
parallel Closure::fromCallable internal
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

try {
    $parallel->run(
        Closure::fromCallable('usleep'), [0.1]);
} catch (\parallel\Runtime\Error\IllegalFunction $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
%S
string(%d) "illegal function type (internal)"
