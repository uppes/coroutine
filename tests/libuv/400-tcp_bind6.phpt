--TEST--
Check for tcp bind
--SKIPIF--
<?php if (true) print "Skip, broken on Windows and Linux"; ?>
--FILE--
<?php
$tcp = uv_tcp_init();
uv_tcp_bind6($tcp, uv_ip6_addr('::1', 0));
uv_listen($tcp,100, function($server) {
    $client = uv_tcp_init();
    uv_accept($server, $client);
    uv_read_start($client, function($socket, $nRead, $buffer) use ($server) {
        echo $buffer . PHP_EOL;
        uv_close($socket);
        uv_close($server);
    });
});

$addrinfo = uv_tcp_getsockname($tcp);

$c = uv_tcp_init();
$data = uv_ip6_addr($addrinfo['address'], $addrinfo['port']);
print_r($data);
print_r($addrinfo);
uv_tcp_connect($c, $data, function($client, $stat) {
    if ($stat == 0) {
        uv_write($client,"Hello",function($socket, $stat){
            uv_close($socket, function() { });
        });
    }
});

uv_run();
--EXPECT--
Hello
