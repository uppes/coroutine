<?php

// Let's ensure we have optimal performance. Set this simple thing
date_default_timezone_set('America/NewYork');

error_reporting(-1);
ini_set("display_errors", 1);
echo "CO-ROUTINE EXAMPLE. WEBSERVER" . PHP_EOL;

chdir(__DIR__);

require_once('../../Coroutine/Scheduler.php');
require_once('../../Coroutine/Task.php');
require_once('../../Coroutine/Co.php');
require_once('../../Coroutine/Call.php');
require_once('../../Coroutine/CoSocket.php');

echo "LIBS LOADED" . PHP_EOL;


function server($port) {
    echo "SERVER LISTENING ON: $port" . PHP_EOL . PHP_EOL;;

    $socket = @stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new \Exception($errStr, $errNo);

    stream_set_blocking($socket, 0);

    $socket = new CoSocket($socket);
    while (true) {
        yield coroutine(
            handleClient(yield $socket->accept())
        );
    }
}


function loadTemplateFile($template, $vars){
    extract($vars, EXTR_OVERWRITE);
    $output = '';
    ob_start();
    require $template;
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
}


function handleClient($socket) {
    $data = (yield $socket->read(8192));
    $output = "Received following request:\n\n$data";

    $input = explode(" ", $data);
    if (empty($input[1])) {
        $input[1] = "index.html";
    }
    $input = $input[1];
    $fileinfo = pathinfo($input);
    $mime = "text/html";

    if (!empty($fileinfo['extension'])) {
        switch ($fileinfo['extension']) {
            case "png";
                $mime = "image/png";
                break;
            case "jpg";
                $mime = "image/jpeg";
                break;
            case "ico";
                $mime = "image/x-icon";
                break;
            default:
                $mime = "text/html";
        }
    }

    if ($input == "/") {
        $input = "/index.html";
    }

    if ($input == "/test") {
        $input = "/test.php";
    }

    $input = ".$input";

    if (file_exists($input) && is_readable($input)) {
        print "Serving $input\n";

        if (strstr($input, '.php')) {
            $contents = loadTemplateFile($input, []);
        } else {
            $contents = file_get_contents($input);
        }

        $output = "HTTP/1.0 200 OK\r\nServer: APatchyServer\r\nConnection: close\r\nContent-Type: $mime\r\n\r\n$contents";
    } else {
        $contents = "The file you requested does not exist. Sorry!";
        $output = "HTTP/1.0 404 OBJECT NOT FOUND\r\nServer: APatchyServer\r\nConnection: close\r\nContent-Type: text/html\r\n\r\n$contents";
    }

    //$output = "Hello World!" . PHP_EOL;
    $msgLength = strlen($output);

/*    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$output
RES;*/

    yield $socket->write($output);
    yield $socket->close();
}


$scheduler = new Scheduler;
$scheduler->coroutine(server(5000));
$scheduler->run();
