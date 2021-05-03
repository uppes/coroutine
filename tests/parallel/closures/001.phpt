--TEST--
Check closures in arginfo/argv (OK)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$runtime = new \parallel\Runtime;
$runtime->run(function(Closure $arg){
    var_dump($arg());
}, function(){
    return true;
});
?>
--EXPECTF--
closure://function(\Closure $arg){
%S
}:3:
bool(true)
