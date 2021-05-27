--TEST--
parallel killed
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$co = $parallel->run(function($a, $b){
	$c = 0;
	while(1) {
		$c += $a + $b;
	}
}, 1,2);

$future = $parallel->run(function(){
	echo "NO";
	return true;
});

$parallel->kill();

try {
	$future->value();
} catch (\parallel\Future\Error\Killed $ex) {
	var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(%d) "cannot retrieve value"
