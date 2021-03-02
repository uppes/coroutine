--TEST--
Check for uv_exepath
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
$path = uv_exepath();

echo (int)preg_match("/php/", $path, $match);
--EXPECT--
1
