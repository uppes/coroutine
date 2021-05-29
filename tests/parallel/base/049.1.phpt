--TEST--
parallel cancellation (runtime killed)
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if ('\\' === \DIRECTORY_SEPARATOR) {
    die("skip");
}
?>
--FILE--
<?php
include 'vendor/autoload.php';
$parallel = new \parallel\Runtime();
$sync     = \parallel\Channel::make("sync");

$future = $parallel->run(function(){
    $sync = \parallel\Channel::open("sync");
    $sync->recv();

    while(1){
        echo "waiting...\n";
        usleep(10000);
    }
}, $sync);

$sync->send(true);

$parallel->kill();

try {
    $future->cancel();
} catch (\parallel\Future\Error\Killed $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
waiting...
waiting...
waiting...
string(%d) "runtime executing task was killed"
--XLEAK--
The interrupt we use for cancellation is not treated in a thread safe way in core
