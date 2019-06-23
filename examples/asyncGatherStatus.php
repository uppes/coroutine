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
    $tasks = [];
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
}

function get_website_status($url) 
{yield;
    $id = yield \task_id();
    $object = yield \file_open($url);
    $status = \file_status($object);
    \file_close($object);
    //[$meta, $status, $retry] = yield \head_uri($url);
    print "task: $id, code: $status".EOL;
    // if ($retry === true)
       // echo "task $id, had to be retried!".EOL;
    return $status;
}

function lapse() {
    $i = 1;
    while(true) {
        echo '.';
        $i++;
        if ($i == 100) {
            break;
        }
        yield;
    }
}

function main() 
{    
    yield \await('lapse');
    chdir(__DIR__);
    $object = yield \file_open('.'.\DS.'list.txt');
    $websites = yield \file_lines($object);
    \file_close($object);
    if ($websites !== false) {
        $t0 = \microtime(true);
        $data = yield from get_statuses($websites);
        $t1 = \microtime(true);
        print $data.EOL;
        print("getting website statuses took ".(float) ($t1-$t0)." seconds");
    }
}

\coroutine_run(\main());
