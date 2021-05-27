--TEST--
Check closures cached function check
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
string(2) "OK"
string(2) "OK"
