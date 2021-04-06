<?php

include 'vendor/autoload.php';

use function Async\Path\file_get;
use function Async\Stream\{messenger_for, net_accept, net_close, net_local, net_response, net_server, net_write};

function main($port)
{
  $count = 0;
  $server = yield net_server($port);
  print('Server is running on: ' . net_local($server) . \EOL);

  while (true) {
    $count++;
    // Will pause current task and wait for connection, all others tasks will continue to run
    $connected = yield net_accept($server);
    // Once an connection is made, will create new task and continue execution there, will not block
    yield \away(\handleClient($connected, $count));
  }
}

function handleClient($socket, int $counter)
{
  yield \stateless_task();
  // add 2 second delay to every 10th request
  if ($counter % 10 === 0) {
    print("Adding delay. Count: " . $counter . \EOL);
    yield \sleep_for(2);
  }

  $html = messenger_for('response');
  $contents = yield file_get('hello.html');
  if (\is_string($contents)) {
    $output = net_response($html, $contents, 200);
  } else {
    $output = net_response($html, "The file you requested does not exist. Sorry!", 404);
  }

  yield net_write($socket, $output);
  yield net_close($socket);
}

\coroutine_run(\main(8080));
