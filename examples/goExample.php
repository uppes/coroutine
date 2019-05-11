<?php
/**
 * @see https://www.golang-book.com/books/intro/10
 */
include 'vendor/autoload.php';

function f(int $n) {
    for ($i = 0; $i < 10; $i++) {
      print ($n. ":".$i.' ');
      $amt = $n * 0.2;
      //yield \async_sleep($amt);
      \sleep($amt);
      if ($i == 9)
        print("\n");
      yield;
    }
  }
  
  function main() {
    for ($i = 0; $i < 10; $i++) {
      yield \await('f',  $i);
    }

    //yield \read_input();
  }

\coroutine_create( \main() );
\coroutine_run();
