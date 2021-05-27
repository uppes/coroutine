<?php

namespace Async\examples\parallel;

use Async\examples\parallel\A;
use \parallel\Runtime;

class B
{
  public function executeParallel()
  {
    $task = function ($o) {
      echo $o->getOne();
    };

    $runtime    = new Runtime();
    $runtime->run($task, new A());
    $runtime->close();
  }
}
