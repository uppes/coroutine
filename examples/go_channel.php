<?php
/**
 * @see https://golangbot.com/channels/
 * @see https://play.golang.org/p/EejiO-yjUQ
 */
include 'vendor/autoload.php';

use Async\Coroutine\Channel;

function hello(Channel $channel) {
	print("hello go routine is going to sleep\n");
	yield \sleep_for(5);
	print("\nhello go routine awake and going to write to channel\n");
	yield \sender($channel, 'true');
}

function main() {
  $channel = yield \make();
  print("Main going to call hello go goroutine\n");
  yield \go('hello', $channel);
  yield \receiver($channel);
  $done = yield \receive($channel);
  print("\nMain received data: $done");
}

\coroutine_run(\main());
