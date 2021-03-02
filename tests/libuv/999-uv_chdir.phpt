--TEST--
Check for uv_chdir
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
uv_chdir(dirname(__FILE__));
if (uv_cwd() == dirname(__FILE__)) {
  echo "OK";
} else {
  echo "FAILED: expected " . dirname(__FILE__) . ", but " . uv_cwd();
}
?>
--EXPECTF--
OK
