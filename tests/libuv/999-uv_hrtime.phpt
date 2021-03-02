--TEST--
Check for uv_hrtime
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
/* is this correct ?*/
$hrtime = uv_hrtime();

--EXPECT--
