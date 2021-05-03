--TEST--
bailed
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime(sprintf("%s/bootstrap.inc", __DIR__));

try {
	$parallel->run(function(){
		thrower();
	});
} catch (Error $er) {
	/* can't catch here what is thrown in runtime */
}
?>
--EXPECTF--
Fatal error: Uncaught Exception:%S

#0 closure://function(){
%S
%S
#1 closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S    }(7): {closure}()
#2 %S
#3 {main} in %S
Stack trace:
#0 %S
#1 %S
#2 %S
#3 [internal function]: %S
