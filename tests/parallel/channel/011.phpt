--TEST--
Check channel send arguments
--SKIPIF--
<?php
if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
use \parallel\Channel;

$channel = Channel::make("buffer", Channel::Infinite);

try {
    $channel->send(new DateTime);
    $data = $channel->recv();
    print("No! value of type DateTime is illegal\n");
    var_dump($data);
} catch (\parallel\Channel\Error\IllegalValue $th) {
    var_dump($th->getMessage());
}
?>
--EXPECTF--
No! value of type DateTime is illegal
object(DateTime)#%d (3) {
  ["date"]=>
  string(%d) %S
  ["timezone_type"]=>
  int(3)
  ["timezone"]=>
  string(3) "UTC"
}
