--TEST--
Copy arguments (OK)
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
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
