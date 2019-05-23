<?php
include 'vendor/autoload.php';

use Async\Coroutine\StreamSocket;
/**
 * Converted example of https://github.com/jimmysong/asyncio-examples from: 
 * @see https://youtu.be/qfY2cqjJMdw
 */
function get_statuses($websites) 
{
    $statuses = ['200' => 0, '400' => 0];
	foreach($websites as $website) {
		$tasks[] = yield \await('get_website_status', $website);
    }
    
    $taskStatus = yield \gather($tasks);
	foreach($taskStatus as  $id => $status) {
        if (!$status)
            $statuses[$status] = 0;
		else
			$statuses[$status] += 1;
    }
    
    return json_encode($statuses);
};

function get_website_status($url) 
{
    $id = yield \async_id();
    $class = yield \open_file(null, $url);
    yield \file_meta($class);
    $status = \file_status($class);
    print "task: $id, code: $status".EOL;
    \close_file($class);
    return $status;
};

function main() 
{
    chdir(__DIR__);
    $websites = \file('.\\'.'list.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($websites !== false) {
        $t0 = \microtime(true);
        $data = yield from get_statuses($websites);
        $t1 = \microtime(true);
        print $data.EOL;
        print("getting website statuses took ".(float) ($t1-$t0)." seconds");
    }
};

\coroutine_run(\main());
