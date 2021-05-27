--TEST--
parallel task check cached, Future used second time
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

$closure = function() {
    echo "OK\n";
};

$parallel->run($closure);

$future = $parallel->run($closure);

$future->value();
?>
--EXPECT--
OK
OK
