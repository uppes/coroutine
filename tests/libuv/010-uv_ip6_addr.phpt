--TEST--
Check for uv_ip6_addr
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
var_dump(uv_ip6_addr("::0",0));
--EXPECTF--
object(UVSockAddrIPv6)#%d (%d) {
}
