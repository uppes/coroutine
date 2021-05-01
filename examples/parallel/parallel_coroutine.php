<?php

include 'vendor/autoload.php';

function main()
{
  $runtime = new \parallel\Runtime;

  $future = $runtime->run(function () {
    for ($i = 0; $i < 500; $i++) {
      echo "*";
      sleep(1);
    }

    return "easy";
  });

  yield away(function () {
    for ($i = 0; $i < 500; $i++) {
      echo ".";
      yield sleep_for(1);
    }
  });

  printf("\nUsing \\parallel\\Runtime is %s\n", $future->value());
}

\coroutine_run(main());
