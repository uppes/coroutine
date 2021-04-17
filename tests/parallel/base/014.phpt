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
		print('No "illegal instruction (function) on line 1 of task", all good!'. PHP_EOL);
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
		print('No "illegal instruction (function) in closure on line 1 of task", all good!'. PHP_EOL);
    });
} catch (\Error $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
No "illegal instruction (function) on line 1 of task", all good!
No "illegal instruction (function) in closure on line 1 of task", all good!
