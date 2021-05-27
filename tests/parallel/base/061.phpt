--TEST--
parallel runtime copy
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
$runtime = new \parallel\Runtime();

try {
    \parallel\run(function() use($runtime){
        $runtime->run(function(){
            echo "hi\n";
        });
    });
} catch (\parallel\Runtime\Error\IllegalVariable $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
string(%d) "illegal variable"
