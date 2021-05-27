<?php

namespace Async\examples\parallel;

use parallel\Channel;

class MyClass
{
  private $x;

  public function __construct()
  {
    $this->x = $this;
    $f = function () {
    };

    $ch = new Channel();
    \parallel\run(function (Channel $ch) {
      echo "X";
      $ch->recv();
      echo "Y";
    }, $ch);

    echo "A";
    $ch->send($f);

    echo "B";
  }
}
