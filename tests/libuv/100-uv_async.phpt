--TEST--
Check for uv_async
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
$loop = uv_default_loop();
$async = uv_async_init($loop, function($async) {
    echo "Hello";
    uv_close($async);
});

uv_async_send($async);

uv_run();
?>
--EXPECT--
Hello
