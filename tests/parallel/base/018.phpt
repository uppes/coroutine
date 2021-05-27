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
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, DateTime $arg2) {});
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2, DateTime ... $arg3) {});
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function() : DateTime {});
} catch (\parallel\Runtime\Error\IllegalReturn $ex) {
	var_dump($ex->getMessage());
}
try {
	$parallel->run(function(&$arg) {
		print('No "illegal parameter (reference) accepted by task at argument 1", all good!'. PHP_EOL);
	}, 1);
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, &$arg2) {
		print('No "illegal parameter (reference) accepted by task at argument 2", all good!'. PHP_EOL);
	}, 1,2);
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2, & ... $arg3) {
		print('No "illegal parameter (reference) accepted by task at argument 3", all good!'. PHP_EOL);
	}, 1,2,3);
} catch (\parallel\Runtime\Error\IllegalParameter $ex) {
	var_dump($ex->getMessage());
}

try {
	$future = $parallel->run(function & () : int {
		$var = 42;

		return $var;
	});
	print('No "illegal return (reference) from task", returned: ' . $future->value() . PHP_EOL);
} catch (\parallel\Runtime\Error\IllegalReturn  $ex) {
	var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(%d) "illegal parameter"
string(%d) "illegal parameter"
string(%d) "illegal parameter"
string(%d) "return of task ignored by caller, caller must retain reference to Future"
No "illegal parameter (reference) accepted by task at argument 1", all good!
No "illegal parameter (reference) accepted by task at argument 2", all good!
No "illegal parameter (reference) accepted by task at argument 3", all good!
No "illegal return (reference) from task", returned: 42
