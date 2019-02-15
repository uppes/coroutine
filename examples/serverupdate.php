<?php
include 'vendor/autoload.php';

//use Async\Coroutine\CoSocket;
use Async\Coroutine\Coroutine;

function server($port) {
    echo "Starting server at port $port...\n";

   // $socket = @\stream_socket_server("tcp://localhost:$port", $errNo, $errStr);

    //if (!$socket)
    //    throw new \Exception($errStr, $errNo);

   // \stream_set_blocking($socket, 0);

    $socket = \createSocket($port);
    //$socket = new CoSocket($socket);
    while (true) {
        yield from \async('handleClient', yield \acceptSocket($socket) );
    }
}

function handleClient($socket) {
    $data = yield \readSocket($socket, 8192);

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

    yield \writeSocket($socket, $response);
    yield \closeSocket($socket);
}


$coroutine = new Coroutine();
$coroutine->addTask(server(8000));
$coroutine->run();
