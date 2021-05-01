--TEST--
Copy try
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';

$parallel = new parallel\Runtime();

$future = $parallel->run(function(){
	try {
		throw Error();
	} catch(Error $ex) {
		echo "OK\n";
	} finally {
		return true;
	}
});

var_dump($future->value());
?>
--EXPECT--
OK
bool(true)
