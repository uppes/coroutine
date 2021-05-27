--TEST--
parallel check cache hit literal string
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip";
if (ini_get("opcache.enable_cli")) {
    die("skip opcache must not be loaded");
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime;
$future = $parallel->run(function(){
    return "Foo";
});

var_dump($future->value());
?>
--EXPECT--
string(3) "Foo"
