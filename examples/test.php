<?php
include 'vendor/autoload.php';

use Async\Task\Scheduler;

$start = microtime(true);
/**
 * @param mixed & $return
 * @return Generator
 */
function task1(&$return)
{
    echo 'task1:start ', microtime(true), PHP_EOL;
    $return = (yield file_get_contents('http://www.weather.com.cn/data/cityinfo/101270101.html'));
    echo 'task1:end ', microtime(true), PHP_EOL;
}

/**
 * @param mixed & $return
 * @return Generator
 */
function task2(&$return)
{
    echo 'task2:start ', microtime(true), PHP_EOL;
    $return = (yield file_get_contents('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=yourtoken'));
    echo 'task2:end ', microtime(true), PHP_EOL;
}

$scheduler = new Scheduler();

$t1 = task1($return1);
$t2 = task2($return2);


$scheduler->coroutine($t1);
$scheduler->coroutine($t2);

$scheduler->run();

var_dump($return1, $return2);

$end = microtime(true) - $start;
echo $end;