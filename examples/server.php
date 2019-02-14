<?php
include 'vendor/autoload.php';

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;

function server($port) {
    echo "Starting server at port $port...\n";

    $socket = @\stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new \Exception($errStr, $errNo);

    \stream_set_blocking($socket, 0);

    while (true) {
        yield \asyncReadStream($socket);
        $clientSocket = \stream_socket_accept($socket, 0);
        yield from \async('handleClient', $clientSocket);
    }
}

function handleClient($socket) {
    yield \asyncReadStream($socket);
    $data = \fread($socket, 8192);

    $msg = "Received following request:\n\n$data";
    $msgLength = \strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield \asyncWriteStream($socket);
    \fwrite($socket, $response);

    \fclose($socket);
}

$coroutine = new Coroutine;
$coroutine->addTask(server(8000));
$coroutine->run();
