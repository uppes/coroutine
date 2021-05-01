--TEST--
parallel may accept arrays
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$var = $parallel->run(function($array){
	$array[0] *= 10;
	$array[1] *= 10;
	$array[2] *= 10;

	return $array;
}, $argv = [1,2,3, "hello"]);

var_dump($var->value());

var_dump($argv);
?>
--EXPECTF--
array(4) {
  [0]=>
  int(10)
  [1]=>
  int(20)
  [2]=>
  int(30)
  [3]=>
  string(5) "hello"
}
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
