<?php

include 'vendor/autoload.php';

function repeat()
{
    $counter = 0;
    while (true) {
        $counter++;
        \printf(".");
        yield;
    }
}

function main()
{
    \file_operation();
    yield \away(\repeat());

    echo  \EOL . 'touch file' . \EOL;
    $bool = yield \file_touch('./tmpTouch');
    \var_dump($bool);

    echo  \EOL . 'file size' . \EOL;
    $size = yield \file_size("./tmpTouch");
    \var_dump($size);

    echo  \EOL . 'file exist' . \EOL;
    $exist = yield \file_exist("./tmpTouch");
    \var_dump($exist);

    echo  \EOL . 'unlink file' . \EOL;
    $exist = yield \file_unlink("./tmpTouch");
    \var_dump($exist);
    yield \shutdown();
}

\coroutine_run(\main());
