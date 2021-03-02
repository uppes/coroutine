--TEST--
Check for uv_uptime
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
$uptime = uv_uptime();

echo (int)is_float($uptime);
--EXPECT--
1
