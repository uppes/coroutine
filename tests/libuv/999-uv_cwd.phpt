--TEST--
Check for uv_cwd
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
$cwd = uv_cwd();

$expected = getcwd();

if ($cwd == $expected) {
  echo "OK" . PHP_EOL;
} else {
  echo "FAILED: expected {$expected}, but {$cwd}" . PHP_EOL;

}
--EXPECT--
OK
