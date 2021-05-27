--TEST--
parallel streams
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$serving = new \parallel\Runtime();
$sending = new \parallel\Runtime();

$future = $serving->run(function() {
$server = stream_socket_server("tcp://127.0.0.1:9999", $errno, $errstr);

	if ($client = stream_socket_accept($server, 10)) {
		echo "OK\n";
	}
});

$sending->run(function(){
	$sock = fsockopen("127.0.0.1", 9999);

	if ($sock) {
		fclose($sock);
	}
});

?>
--EXPECT--
OK
