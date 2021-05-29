--TEST--
parallel auto globals
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
?>
--INI--
variables_order=EGPCS
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();

$parallel->run(function(){
	if (count($_SERVER) > 0) {
		echo "SERVER\n";
	}
});

$parallel = new \parallel\Runtime();

$parallel->run(function(){
    $closure = function() {
        return $_SERVER;
    };

    if (count($closure()) > 0) {
        echo "NESTED SERVER\n";
    }
});
?>
--EXPECT--
SERVER
NESTED SERVER
