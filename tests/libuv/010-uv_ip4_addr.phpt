--TEST--
Check for uv_ip4_addr
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
var_dump(uv_ip4_addr("0.0.0.0",0));
--EXPECTF--
object(UVSockAddrIPv4)#7 (0) {
}
