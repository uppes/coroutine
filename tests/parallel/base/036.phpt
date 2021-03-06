--TEST--
parallel return values
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", ">=")) {
    die("skip php 7.4 required");
}
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

try {
	$parallel->run(function(){
		return;
	});
	echo "No return reference error!\n";
} catch (\parallel\Runtime\Error\IllegalReturn $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function(){
		return null;
	});
	echo "No return reference error!\n";
} catch (\parallel\Runtime\Error\IllegalReturn $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function(){
		return 42;
	});
	echo "No return reference error!\n";
} catch (\parallel\Runtime\Error\IllegalReturn $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function(){
		return $var;
	});
} catch (\parallel\Runtime\Error\IllegalReturn $ex) {
	var_dump($ex->getMessage());
}

try {
	$parallel->run(function(){
		echo "OK\n";
	});
} catch (Error $ex) {
	var_dump($ex->getMessage());
}
?>
--EXPECTF--
No return reference error!
No return reference error!
No return reference error!
PHP Notice:  Undefined variable: var in closure://function(){
%S
%S
string(%d) "return of task ignored by caller, caller must retain reference to Future"
OK
