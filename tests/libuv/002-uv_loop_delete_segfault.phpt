--TEST--
Segmentation fault after uv_loop_delete
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
$loop = uv_loop_new();
uv_loop_delete($loop);
--EXPECTF--
