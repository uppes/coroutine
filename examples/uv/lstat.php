<?php

uv_fs_lstat(uv_default_loop(), __FILE__, function($stat, $date) {
    var_dump($date);
});

uv_run();
