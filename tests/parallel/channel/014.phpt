--TEST--
Check Channel comparison
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

$lhs = Channel::make("channel");
$rhs = Channel::open("channel");
$ohs = Channel::make("none");

if ($lhs == $ohs) {
    echo "FAIL";

    var_dump($lhs, $rhs, $ohs,
        ($lhs == $ohs), ($lhs == $rhs));
} else if ($lhs == $rhs) {
    echo "OK\n";
}
?>
--EXPECT--
OK
