<?php

include 'vendor/autoload.php';

/*********************************************
 * Sample parallel functional API
 *
 * Scenario
 * -------------------------------------------
 * Given a large number of rows of
 * data to process, divide the work amongst
 * a set of workers.  Each worker is responsible
 * for finishing their assigned task.
 *
 * In the code below, assume we have arbitrary
 * start and end IDs (rows) - we will try to
 * divide the number of IDs (rows) evenly
 * across 8 workers.  The workers will get the
 * following batches to process to completion:
 *
 * Total number of IDs (rows): 1371129
 * Each worker will get 171392 IDs to process
 *
 * Worker 1: IDs from 11001 to 182393
 * Worker 2: IDs from 182393 to 353785
 * Worker 3: IDs from 353785 to 525177
 * Worker 4: IDs from 525177 to 696569
 * Worker 5: IDs from 696569 to 867961
 * Worker 6: IDs from 867961 to 1039353
 * Worker 7: IDs from 1039353 to 1210745
 * Worker 8: IDs from 1210745 to 1382130
 *
 * Each worker then processes 5000 rows at a time
 * until they are done with their assigned work
 *
 *********************************************/

$minId = 11001;
$maxId = 1382130;
$workers = 8;
$totalIds = $maxId - $minId;
// Try to divide IDs evenly across the number of workers
$batchSize = ceil($totalIds / $workers);
// The last batch gets whatever is left over
$lastBatch = $totalIds % $batchSize;
// The number of IDs (rows) to divide the overall
// task into sub-batches
$rowsToFetch = 5000;

print "Total IDs: " . $totalIds . "\n";
print "Batch Size: " . $batchSize . "\n";
print "Last Batch: " . $lastBatch . "\n";

$producer = function (int $worker, int $startId, int $endId, int $fetchSize) {
  $tempMinId = $startId;
  $tempMaxId = $tempMinId + $fetchSize;
  $fetchCount = 1;

  print "Worker " . $worker . " working on IDs from " . $startId . " to " . $endId . "\n";

  while ($tempMinId < $endId) {
    for ($i = $tempMinId; $i < $tempMaxId; $i++) {
      $usleep = rand(500000, 1000000);
      usleep($usleep);
      print "Worker " . $worker .  " finished batch " . $fetchCount . " from ID " . $tempMinId . " to " . $tempMaxId . "\n";
      // Need to explicitly break out of the for loop once complete or else it will forever process only the first sub-batch
      break;
    }

    // Now we move on to the next sub-batch for this worker
    $tempMinId = $tempMaxId;
    $tempMaxId = $tempMinId + $fetchSize;
    if ($tempMaxId > $endId) {
      $tempMaxId = $endId;
    }
    // Introduce some timing randomness
    $sleep = rand(1, 5);
    sleep($sleep);
    $fetchCount++;
  }

  // This worker has completed their entire batch
  print "Worker " . $worker .  " finished\n";
};

// Create our workers and have them start working on their task
// In this case, it's a set of 171392 IDs to process
for ($i = 0; $i < $workers; $i++) {
  $startId = $minId + ($i * $batchSize);
  $endId = $startId + $batchSize;
  if ($i == ($workers - 1)) {
    $endId = $maxId;
  }
  \parallel\run($producer, ($i + 1), $startId, $endId, $rowsToFetch);
}
