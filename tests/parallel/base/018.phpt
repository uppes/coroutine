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
string(%d) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S   } on line 7 and exactly 1 expected
%S
#0 closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S    }(7): {closure}()
#1 %S
#2 {main}"
string(%d) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S    } on line 7 and exactly 2 expected
%S
#0 closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S    }(7): {closure}()
#1 %S
#2 {main}"
string(%d) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S    } on line 7 and exactly 2 expected
%S
#0 closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S    }(7): {closure}()
#1 %S
#2 {main}"
string(%S) "Return value of {closure}() must be an instance of DateTime, none returned
%S
#0 closure://function () use ($task, $args, $include, $___parallel___) {
%S      if (!empty($include) && \is_string($include))
%S        require $include;

%S      \parallel_setup($___parallel___);
%S      return $task(...$args);
%S    }(7): {closure}()
#1 %S
#2 {main}"
No "illegal parameter (reference) accepted by task at argument 1", all good!
No "illegal parameter (reference) accepted by task at argument 2", all good!
No "illegal parameter (reference) accepted by task at argument 3", all good!
No "illegal return (reference) from task", returned: 42
