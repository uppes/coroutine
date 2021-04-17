--TEST--
Copy arginfo (FAIL)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

try {
	$parallel->run(function(DateTime $arg) {});
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, DateTime $arg2) {});
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2, DateTime ... $arg3) {});
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function() : DateTime {});
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}
try {
	$parallel->run(function(&$arg) {
		print('No "illegal parameter (reference) accepted by task at argument 1", all good!'. PHP_EOL);
	}, 1);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, &$arg2) {
		print('No "illegal parameter (reference) accepted by task at argument 2", all good!'. PHP_EOL);
	}, 1,2);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2, & ... $arg3) {
		print('No "illegal parameter (reference) accepted by task at argument 3", all good!'. PHP_EOL);
	}, 1,2,3);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$future = $parallel->run(function & () : int {
		$var = 42;

		return $var;
	});
	print('No "illegal return (reference) from task", returned: ' . $future->value() . PHP_EOL);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(635) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $argv, $file) {
%S        if (!empty($file) && \is_string($file))
%S          include $file;
%S
%S        return \flush_value($task(...$argv), 50);
%S      } on line 6 and exactly 1 expected
%S
#0 closure://function () use ($task, $argv, $file) {
%S        if (!empty($file) && \is_string($file))
%S          include $file;
%S
%S        return \flush_value($task(...$argv), 50);
%S      }(6): {closure}()
#1 %S
#2 {main}"
string(635) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $argv, $file) {
%S        if (!empty($file) && \is_string($file))
%S          include $file;
%S
%S        return \flush_value($task(...$argv), 50);
%S      } on line 6 and exactly 2 expected
%S
#0 closure://function () use ($task, $argv, $file) {
%S        if (!empty($file) && \is_string($file))
%S          include $file;
%S
%S        return \flush_value($task(...$argv), 50);
%S      }(6): {closure}()
#1 %S
#2 {main}"
string(635) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $argv, $file) {
%S        if (!empty($file) && \is_string($file))
%S          include $file;
%S
%S        return \flush_value($task(...$argv), 50);
%S      } on line 6 and exactly 2 expected
%S
#0 closure://function () use ($task, $argv, $file) {
%S        if (!empty($file) && \is_string($file))
%S          include $file;
%S
%S        return \flush_value($task(...$argv), 50);
%S      }(6): {closure}()
#1 %S
#2 {main}"
string(440) "Return value of {closure}() must be an instance of DateTime, none returned
%S
#0 closure://function () use ($task, $argv, $file) {
%S        if (!empty($file) && \is_string($file))
%S          include $file;
%S
%S        return \flush_value($task(...$argv), 50);
%S      }(6): {closure}()
#1 %S
#2 {main}"
No "illegal parameter (reference) accepted by task at argument 1", all good!
No "illegal parameter (reference) accepted by task at argument 2", all good!
No "illegal parameter (reference) accepted by task at argument 3", all good!
No "illegal return (reference) from task", returned: 42
