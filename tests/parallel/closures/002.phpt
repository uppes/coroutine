--TEST--
Check closures in return
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$runtime = new \parallel\Runtime;

$future = $runtime->run(function(Closure $closure) : Closure {
    return $closure;
}, function(){
    return true;
});

var_dump(($future->value())());
?>
--EXPECT--
bool(true)
