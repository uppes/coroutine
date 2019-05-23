<?php
/**
 * @see https://gobyexample.com/goroutines
 */
include 'vendor/autoload.php';

function e(string $from) {
  for ($i = 0; $i < 3; $i++) {
      print($from.":".$i.EOL);
  }
}

function f(string $from) {
  for ($i = 0; $i < 3; $i++) {
      yield;
      print($from.":".$i.EOL);
  }
}

function anonymous(string $msg) {
  print($msg.EOL);
  yield;
}
  
  function main() {
    // Suppose we have a function call `e(s)`. Here's how
    // we'd call that in the usual way, running it
    // synchronously.
    e("direct");

    // To invoke this function in a goroutine, use
    // `go f(s)`. This new goroutine will execute
    // concurrently with the calling one.
    yield \go('f', "goroutine");

    // - Needs to implemented correctly -
    // You can also start a goroutine for an anonymous
    // function call.
    //yield \go(function(string $msg) {
    //    print($msg);
    //}, "going");
    yield \go('anonymous', "going");

    // Our two function calls are running asynchronously in
    // separate goroutines now, so execution falls through
    // to here. This `Scanln` requires we press a key
    // before the program exits.
    yield \read_input();
    print("done".EOL);
  }

\coroutine_run(\main());
