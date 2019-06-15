<?php
include 'vendor/autoload.php';

use Async\Coroutine\StreamSocket;
/**
 * Converted example of https://github.com/jimmysong/asyncio-examples from: 
 * @see https://youtu.be/qfY2cqjJMdw
 */
function get_statuses($websites) 
{
    $statuses = ['200' => 0, '400' => 0, '405' => 0];
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
    $id = yield \task_id();
    [$meta, $status] = yield \head_uri($url);
    print "task: $id, code: $status".EOL;
    return $status;
};

function main() 
{
    chdir(__DIR__);
    $object = yield \open_file(null, '.'.\DS.'list.txt');
    $websites = yield \file_lines($object);
    \close_file($object);
    if ($websites !== false) {
        $t0 = \microtime(true);
        $data = yield from get_statuses($websites);
        $t1 = \microtime(true);
        print $data.EOL;
        print("getting website statuses took ".(float) ($t1-$t0)." seconds");
    }
};

\coroutine_run(\main());
