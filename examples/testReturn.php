<?php
include 'vendor/autoload.php';

use Async\Task\Scheduler;

$start = microtime(true);
/**
 * @return Generator
 */
function task1()
{
    echo 'task1:start ', microtime(true), PHP_EOL;
    $ret = (yield file_get_contents('http://www.weather.com.cn/data/cityinfo/101270101.html'));
    echo 'task1:end ', microtime(true), PHP_EOL;
    return $ret;
}

/**
 * @return Generator
 */
function task2()
{
    echo 'task2:start ', microtime(true), PHP_EOL;
    $ret = (yield file_get_contents('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=yourtoken'));
    echo 'task2:end ', microtime(true), PHP_EOL;
    return $ret;
}

$scheduler = new Scheduler();

$t1 = task1();
$t2 = task2();

$scheduler->coroutine($t1);
$scheduler->coroutine($t2);

$scheduler->run();

var_dump($t1->getReturn(), $t2->getReturn());//PHP 7+

$end = microtime(true) - $start;
echo $end;