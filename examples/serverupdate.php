<?php
include 'vendor/autoload.php';

use Async\Coroutine\Coroutine;

function server($port) {
    echo "Starting server at port $port...\n";

    $socket = @\stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new \Exception($errStr, $errNo);

    \stream_set_blocking($socket, 0);

    $socket = \asyncCreate($socket);
    while (true) {
        yield from \async('handleClient', yield \asyncAccept($socket) );
    }
}

function handleClient($socket) {
    $data = yield \asyncRead($socket, 8192);

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield \asyncWrite($socket, $response);
    yield \asyncClose($socket);
}


$coroutine = new Coroutine();
$coroutine->addTask(server(8000));
$coroutine->run();
