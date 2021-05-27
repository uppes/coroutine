<?php

include 'vendor/autoload.php';

use parallel\{Runtime, Channel};

main($argv);

function main(array $argv)
{
  if (count($argv) !== 3) {
    echo "Type: hello-parallel.php <number-of-tasks> <maximum-time-of-sleep (in seconds)>" . PHP_EOL;
    echo "Example: hello-parallel.php 5 3" . PHP_EOL;
    die;
  } else {
    $numberOfTasks = intval($argv[1]);
    $maximumTimeOfSleep = intval($argv[2]);
    $t1 = microtime(true);
    parallelize($numberOfTasks, $maximumTimeOfSleep);
    $endTime = microtime(true) - $t1;
    echo PHP_EOL . "Finished $numberOfTasks task(s) in {$endTime}s" . PHP_EOL;
  }
}

function parallelize(int $numberOfTasks, int $maximumTimeOfSleep)
{
  $channel = new Channel();

  $taskIds = array_map(function () use ($maximumTimeOfSleep) {
    return $id = uniqid("task::");
  }, range(0, $numberOfTasks - 1));

  $timesToSleep = array_map(function () use ($maximumTimeOfSleep) {
    return rand(1, $maximumTimeOfSleep);
  }, $taskIds);

  $producer = new Runtime();
  $producerFuture = $producer->run(function (Channel $channel, array $timesToSleep) {
    foreach ($timesToSleep as $timeToSleep) {
      $channel->send($timeToSleep);
    }
  }, $channel, $timesToSleep);

  $consumerFutures = array_map(function (string $id) use ($channel) {
    $runtime = new Runtime();
    return $runtime->run(function (string $id, Channel $channel) {
      $timeToSleep = $channel->recv();
      echo "Hello from $id. I will sleep for $timeToSleep second(s)." . PHP_EOL;
      sleep($timeToSleep);
      echo "$id slept for $timeToSleep second(s)." . PHP_EOL;
      return $timeToSleep;
    }, $id, $channel);
  }, $taskIds);

  wait($consumerFutures);
  wait([$producerFuture]);
}

function wait(array $futures)
{
  return array_map(function ($future) {
    return $future->value();
  }, $futures);
}
