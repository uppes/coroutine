--TEST--
Check for fs_send_file
--SKIPIF--
<?php if (!extension_loaded("uv")) print "skip"; ?>
--FILE--
<?php
define("FIXTURE_PATH", dirname(__FILE__) . "/fixtures/hello.data");

uv_fs_open(uv_default_loop(), FIXTURE_PATH, UV::O_RDONLY, 0, function($read_fd) {
    // $std_err = STDOUT; // phpt doesn't catch stdout as uv_fs_sendfile uses stdout directly.
    $std_err = fopen('php://stdout', 'w');
    uv_fs_sendfile(uv_default_loop(), $std_err, $read_fd, 0, 6, function($result) { });
});

uv_run();
--EXPECT--
Hello
