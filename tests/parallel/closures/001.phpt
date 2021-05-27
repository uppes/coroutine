--TEST--
Check closures in arginfo/argv (OK)
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
$runtime = new \parallel\Runtime;
$runtime->run(function(Closure $arg){
    var_dump($arg());
}, function(){
    return true;
});
?>
--EXPECTF--
bool(true)
