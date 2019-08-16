<?php

include 'vendor/autoload.php';

function error($a) {
    \recover($task, 'skip', $a);
    if ($a == 2) {
        print("Panicking!\n");
        \panic();
    }
}

print "start".\EOL;
function foo($a) {
    print "in defer 3-{$a}".\EOL;
    error($a);
};

function skip($a = 0) {
    print "Skipped!\n";
    print("Recovered in foo($a)\n");
}

function a() {
	print "before defer".\EOL;
	\defer($task, "foo", 1);
	\defer($task, "foo", 2);
	\defer($task, "foo", 3);
	print "after defer".\EOL;
};

a();
print "end".\EOL;
