<?php
/**
 * @see https://www.golang-book.com/books/intro/10
 */
include 'vendor/autoload.php';

function f(int $n) {
    for ($i = 0; $i < 10; $i++) {
      print ($n. ":".$i.' ');
      $amt = ($n + \mt_rand(500, 2000)) * \MS;
      if ($i == 9)
        print("\n");
      \sleep($amt);
      yield;
    }
  }

function main() {
    for ($i = 0; $i < 10; $i++) {
      $func = yield \away(\f($i));
      print ("\nCreated function: f($i), Task id: $func - ");
    }

    print("\nPress enter to exit,\nbut i will be still running,\ngot more tasks iterations to do!\n");
    yield \input_wait();
 }

\coroutine_run(\main());
