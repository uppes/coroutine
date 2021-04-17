--TEST--
ZEND_DECLARE_LAMBDA_FUNCTION
--SKIPIF--
<?php if (((float) \phpversion() >= 8.0)) print "skip"; ?>
--INI--
opcache.optimization_level=0
--FILE--
<?php
include 'vendor/autoload.php';

$runtime = new parallel\Runtime();

$f1 = $runtime->run(function() {
	$closure = function() {
	    return true;
	};
    $result = $closure();
	print_r('bool(' . ($result ? 'true' : 'false') .')' . PHP_EOL);
});

$f1->value();

$f2 = $runtime->run(function() {
	$closure = function() {
	    $result = function(){
	        return true;
	    };
	    return $result();
	};
    $result = $closure();
	print_r('bool('. ($result ? 'true' : 'false') . ')' . PHP_EOL);
});

$f2->value();

try {
    $runtime->run(function() {
        $closure = function() {
            $closure = function() {
                new class{};
            };
        };
		print('No "illegal instruction (new class) in closure on line 2 of task", all good!'. PHP_EOL);
    });
} catch (\Error  $ex) {
    var_dump($ex->getMessage());
}

try {
    $runtime->run(function() {
        $closure = function() {
            $closure = function() {
                class illegal {}
            };
        };
    });
} catch (\Error $ex) {
    var_dump($ex->getMessage());
}
?>
--EXPECTF--
bool(true)
bool(true)
No "illegal instruction (new class) in closure on line 2 of task", all good!
string(%d) "syntax error, unexpected '\' (T_NS_SEPARATOR), expecting identifier (T_STRING)

#0 [internal function]: Opis\Closure\SerializableClosure->unserialize(NULL)
#1 %S
#2 [internal function]: Opis\Closure\SerializableClosure->unserialize('a:5:{s:3:"use";...')
#3 %S
#4 %S
#5 %S
#6 {main}"
