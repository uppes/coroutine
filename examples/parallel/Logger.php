<?php

include 'vendor/autoload.php';

use Async\examples\parallel\BackgroundLogger;

$logger = new BackgroundLogger("php://stdout");
$logger->log("hello world");
$logger->log("I am %s", "here");
