<?php
include 'vendor/autoload.php';

use Async\Coroutine\StreamSocket;
/**
 * Does not work, same as native, no speed improvement. 
 * And need to get results before entering `gather` method.
 * 
 * Converted example of https://github.com/jimmysong/asyncio-examples from: 
 * @see https://youtu.be/qfY2cqjJMdw
 */
function get_statuses($websites) 
{
    $statuses = [];
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
    //$handle = \open_file(null, $url);
    $status = StreamSocket::getStatus($url);
    //$status = \file_status($handle);
    //print \file_meta($handle)['wrapper_data'][0];
    print "task: $id, status code: $status".EOL;
    //\close_file($handle);
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
