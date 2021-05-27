--TEST--
Check closures no nested declarations
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--FILE--
<?php
include 'vendor/autoload.php';
$runtime = new \parallel\Runtime;
$channel = \parallel\Channel::make("channel", \parallel\Channel::Infinite);

try {
    $channel->send(function(){
        new class {};
    });
    $data = $channel->recv();
    var_dump($data);
} catch (\parallel\Channel\Error\IllegalValue $ex) {
    var_dump($ex->getMessage());
}

try {
    $channel->send(function(){
        class nest {}
    });
    $data = $channel->recv();
} catch (\parallel\Channel\Error\IllegalValue $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
object(Closure)#%d (0) {
}
string(78) "syntax error, unexpected '\' (T_NS_SEPARATOR), expecting identifier (T_STRING)"
