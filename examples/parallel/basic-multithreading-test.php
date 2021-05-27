<?php

include 'vendor/autoload.php';

use parallel\Runtime;
use parallel\Channel;

$test = "this var is not accessible in a thread";

// this function will be the threads
$thread_function = function (int $id, Channel $ch) {
  // delay the first thread to simulate better multithreading
  // second thread always finishes first
  $sleep = ($id == 2) ? 1 : 2;
  sleep($sleep);

  // print thread id
  // so it's clear second thread goes first
  // and also you can make sure multithreading is working
  var_dump("thread $id sleep $sleep");

  // try to capture globals, but it's not possible
  echo '$GLOBALS["test"] = ';
  @var_dump($GLOBALS["test"]);

  // the only way to share data is between channels
  $ch->send($sleep);
};

try {
  // each runtime represents a thread
  $r1 = new Runtime();
  $r2 = new Runtime();

  // channel where the date will be sharead
  $ch1 = new Channel();

  // args that will be sent to $thread_function
  $args = array();
  $args[0] = null;
  $args[1] = $ch1;

  // running thread 1
  $args[0] = 1;
  $r1->run($thread_function, ...$args);

  // running thread 2
  $args[0] = 2;
  $r2->run($thread_function, ...$args);

  // receive data from channel
  $x = $ch1->recv();
  $y = $ch1->recv();

  // close channel
  $ch1->close();

  echo "\nData received by the channel: $x and $y";
} catch (Error $err) {
  echo "\nError:", $err->getMessage();
} catch (Exception $e) {
  echo "\nException:", $e->getMessage();
}
