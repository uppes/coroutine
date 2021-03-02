--TEST--
Check for uv_ip6_name
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
$ip = uv_ip6_addr("::1",0);
echo uv_ip6_name($ip) . PHP_EOL;
--EXPECT--
::1
