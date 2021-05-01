--TEST--
return destroyed
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

$future = $parallel->run(function(){
	return new stdClass;
});

var_dump($future->value());
?>
--EXPECTF--
object(stdClass)#%d (0) {
}
