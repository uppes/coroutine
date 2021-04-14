--TEST--
Copy arginfo (FAIL)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new Async\Parallel\Runtime();

try {
	$parallel->run(function(DateTime $arg) {})->value();
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, DateTime $arg2) {})->value();
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2, DateTime ... $arg3) {})->value();
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function() : DateTime {})->value();
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}
try {
	$parallel->run(function(&$arg) {}, 1)->value();
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, &$arg2) {}, 1,2)->value();
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function($arg, $arg2, & ... $arg3) {}, 1,2,3)->value();
} catch (\Error $ex) {
	var_dump($ex->getMessage());
}

	$parallel->run(function & () : int {
		$var = 42;

		return $var;
	})->value();
?>
--EXPECTF--
string(479) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $argv) {
%S
%S

#0 closure://function () use ($task, $argv) {
%S
%S
#1 %S
#2 {main}"
string(479) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $argv) {
%S
%S

#0 closure://function () use ($task, $argv) {
%S
%S
#1 %S
#2 {main}"
string(479) "Too few arguments to function {closure}(), 0 passed in closure://function () use ($task, $argv) {
%S
%S

#0 closure://function () use ($task, $argv) {
%S
%S
#1 %S
#2 {main}"
string(365) "Return value of {closure}() must be an instance of DateTime, none returned

#0 closure://function () use ($task, $argv) {
%S
%S
#1 %S
#2 {main}"
