<?php
include 'vendor/autoload.php';

/**
 * Converted example of https://github.com/jimmysong/asyncio-examples from: 
 * @see https://youtu.be/qfY2cqjJMdw
 */
function get_statuses($websites) 
{
    $statuses = [];
	foreach($websites as $website) {
		$tasks[] = yield await('get_website_status', $website);
	}
    $taskStatus = yield \gather($tasks);
    print_r($taskStatus);
	foreach($taskStatus as $status) {
        if (!$status)
            $statuses[$status] = 0;
		else
			$statuses[$status] += 1;
	}
    print_r($statuses);
};

function get_website_status($url) 
{
    $handle = \open_file(null, $url, 80);
    $response = yield \file_get($handle);
    $status = \file_status($handle);
    print $response.' : '.$status.EOL;
    \close_file($handle);
    return $status;
};

function main() 
{
    chdir(__DIR__);
    $websites = \file('.\\'.'list.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($websites !== false) {
        $t0 = \microtime(true);
        yield from get_statuses($websites);
        $t1 = \microtime(true);
        print("getting website statuses took ".(float) ($t1-$t0)." seconds");
    }
};

\coroutine_run(\main());
