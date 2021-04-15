--TEST--
ZEND_DECLARE_FUNCTION
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

try {
    $parallel->run(function(){
        function test1() {}
    });
} catch (\Error $ex) {
    var_dump($ex->getMessage());
}

try {
    $parallel->run(function(){
        function () {
            function test2() {

            }
        };
    });
} catch (\Error $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
