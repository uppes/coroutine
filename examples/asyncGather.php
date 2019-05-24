<?php
include 'vendor/autoload.php';
/**
 * @see https://docs.python.org/3.7/library/asyncio-task.html#running-tasks-concurrently
 */
function factorial($name, $number) {
    $f = 1;
    foreach (range(2, $number + 1) as $i) {
        print(\EOL."Task {$name}: Compute factorial({$i})...");
        yield \sleep_for(1);
        $f *= $i;
    }
    print(\EOL."Task {$name}: factorial({$number}) = {$f}");
}

function main() {
    # Schedule three calls *concurrently*:
    yield \gather(
        factorial("A", 2),
        factorial("B", 3),
        factorial("C", 4)
    );
}

\coroutine_run(\main());
