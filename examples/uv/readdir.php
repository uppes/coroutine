<?php

uv_fs_scandir(uv_default_loop(), ".", 0, function(int $status, $contents) {
    var_dump($contents);
});

uv_run();
