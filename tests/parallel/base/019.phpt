--TEST--
Copy argv (FAIL)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

try {
	$parallel->run(function($arg) {},
		new DateTime
	);
	print('No "illegal parameter (DateTime) passed to task at argument 1", All good!' . PHP_EOL);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2) {},
		1, new DateTime
	);
	print('No "illegal parameter (DateTime) passed to task at argument 2", All good!' . PHP_EOL);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2, ... $arg3) {},
		1, 2, new DateTime
	);
	print('No "illegal parameter (DateTime) passed to task at argument 3", All good!' . PHP_EOL);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($array) {},
		[new DateTime]
	);
	print('No "illegal parameter (DateTime) passed to task at argument 1", All good!' . PHP_EOL);
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}
?>
--EXPECT--
No "illegal parameter (DateTime) passed to task at argument 1", All good!
No "illegal parameter (DateTime) passed to task at argument 2", All good!
No "illegal parameter (DateTime) passed to task at argument 3", All good!
No "illegal parameter (DateTime) passed to task at argument 1", All good!
