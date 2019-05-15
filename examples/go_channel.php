<?php
/**
 * @see https://golangbot.com/channels/
 * @see https://play.golang.org/p/EejiO-yjUQ
 */
include 'vendor/autoload.php';

use Async\Coroutine\Channel;

function hello(Channel $channel) {
	print("hello go routine is going to sleep\n");
	yield \go_sleep(5);
	print("\nhello go routine awake and going to write to channel\n");
	yield \go_sender($channel, 'true');
}

function main() {
  $channel = yield \go_make();
  print("Main going to call hello go goroutine\n");
  yield \go('hello', $channel);
  yield \go_receiver($channel);    
  $done = yield \go_receive($channel);
  print("\nMain received data: $done");
}

\coroutine_run(\main());
