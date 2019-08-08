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

  function main() {
    // Suppose we have a function call `e(s)`. Here's how
    // we'd call that in the usual way, running it
    // synchronously.
    yield \e("direct");

    // To invoke this function in a goroutine, use
    // `go f(s)`. This new goroutine will execute
    // concurrently with the calling one. will not if `yield` is not present!
    yield \go(\f("goroutine"));

    // You can also start a goroutine for an anonymous
    // function call.
    yield \go(function(string $msg) {
        print($msg.EOL);
    }, "going");

    // Our two function calls are running asynchronously in
    // separate goroutines now, so execution falls through
    // to here. This `Scanln` requires we press a key
    // before the program exits.
    yield \input_wait();
    print("done".EOL);
  }

\coroutine_run(\main());
