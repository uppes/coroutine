--TEST--
Check functional reuse
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$future = \parallel\run(function() {
    global $var;

    $var = 42;
});

$future->value(); # we know that the
                  # runtime started for the first
                  # task is free, the next task
                  # must reuse the runtime

usleep(1000000/2); # we can't be sure how fast the runtime will become available
                   # so we sleep a little here to help the test along
                   # normal code doesn't need to care

\parallel\run(function(){
    global $var;

    var_dump($var);
});
?>
--EXPECTF--
closure://function(){
%S
%S
%S
}:5:
int(42)
