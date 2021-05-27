--TEST--
parallel check arginfo cached
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$closure = function(Closure $closure){

};

$parallel->run($closure, function(){});
$parallel->run($closure, function(){});
echo "OK\n";
?>
--EXPECT--
OK
