--TEST--
parallel future exception
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();
$future   = $parallel->run(function(){
	throw new Exception();

	return false;
});

$future->value();
?>
--EXPECTF--
Fatal error: Uncaught Exception:%S

#0 closure://function () use ($task, $args, $include, $___parallel___) {
%S
%S

%S
%S
%S    }(7): {closure}()
#1 %S
#2 {main} %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 [internal function]: Async\Parallel->markAsFailed(Object(Async\Spawn\Future))
#4 %S
