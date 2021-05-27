<?php

include 'vendor/autoload.php';

use Async\examples\parallel\B;

$b = new B();
$b->executeParallel();
