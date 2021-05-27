--TEST--
parallel copy persistent repeat
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$closure = function(){
    return true;
};

$future[] = $parallel->run($closure);
$future[] = $parallel->run($closure);

foreach ($future as $f)
    var_dump($f->value());
?>
--EXPECT--
bool(true)
bool(true)
