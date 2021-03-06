--TEST--
Copy arguments (OK)
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

$parallel = new parallel\Runtime();

try {
	$parallel->run(function() {
		var_dump(func_get_args());
	},
		1,2,3, "hello"
	);
} catch (Error $ex) {
	var_dump($ex->getMessage());
}
?>
--EXPECTF--
array(4) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
  [3]=>
  string(5) "hello"
}
