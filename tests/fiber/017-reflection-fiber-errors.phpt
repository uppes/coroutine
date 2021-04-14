--TEST--
ReflectionFiber errors
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;
use Async\ReflectionFiber;

function main()
{

$fiber = new Fiber(function () {
    yield Fiber::suspend();
});

$reflection = new ReflectionFiber($fiber);

try {
    $reflection->getTrace();
} catch (Error $error) {
    echo $error->getMessage(), "\n";
}

try {
    $reflection->getExecutingFile();
} catch (Error $error) {
    echo $error->getMessage(), "\n";
}

try {
    $reflection->getExecutingLine();
} catch (Error $error) {
    echo $error->getMessage(), "\n";
}

yield $fiber->start();

var_dump($reflection->getExecutingFile());
var_dump($reflection->getExecutingLine());

yield $fiber->resume();

try {
    $reflection->getTrace();
} catch (Error $error) {
    echo $error->getMessage(), "\n";
}

try {
    $reflection->getExecutingFile();
} catch (Error $error) {
    echo $error->getMessage(), "\n";
}

try {
    $reflection->getExecutingLine();
} catch (Error $error) {
    echo $error->getMessage(), "\n";
}

}

\coroutine_run(main());

--EXPECTF--
Cannot fetch information from a fiber that has not been started or is terminated
Cannot fetch information from a fiber that has not been started or is terminated
Cannot fetch information from a fiber that has not been started or is terminated
string(%d) "%S
int(%d)
Cannot fetch information from a fiber that has not been started or is terminated
Cannot fetch information from a fiber that has not been started or is terminated
Cannot fetch information from a fiber that has not been started or is terminated
