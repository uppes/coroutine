<?php

include 'vendor/autoload.php';

function task(int $num)
{
  print("Task {$num}: request sent" . EOL);
  yield \sleep_for(1);
  print("Task {$num}: response arrived" . EOL);
}

function main()
{
  yield \gather(\array_map('task', \range(1, 3)));
}

\coroutine_run(main());
