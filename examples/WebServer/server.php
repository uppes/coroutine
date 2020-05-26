<?php
/**
 * This also an simpler version of
 * "HOWTO: PHP TCP Server/Client with SSL Encryption using Streams"
 *
 * @see http://blog.leenix.co.uk/2011/05/howto-php-tcp-serverclient-with-ssl.html
 */
include 'vendor/autoload.php';

// Let's ensure we have optimal performance. Set this simple thing
\date_default_timezone_set('America/New_York');

\error_reporting(-1);
\ini_set("display_errors", 1);
echo "CO-ROUTINE EXAMPLE. WEBSERVER" . EOL;

\chdir(__DIR__);

echo "LIBS LOADED" . EOL;

function server($port)
{
    global $i;
    echo "SERVER LISTENING ON: $port" . EOL . EOL;;

    //$socket = \secure_server($port);
    $socket = \create_server($port);
    $i=1;
    while (true) {
        $connectedSocket = yield \server_accept($socket);
        yield \away('handleClient', $connectedSocket);
    }
}

function loadFile($template, $vars, $fd)
{
    yield \file_close($fd);

    \extract($vars, \EXTR_OVERWRITE);
    $output = '';

    \ob_start();
    require $template;
    $output = \ob_get_contents();
    \ob_end_clean();

    return $output;
}


function handleClient($socket)
{
    global $i;
    $data = yield \server_read($socket, 8192);

    $ip = \remote_address($socket);
    print "New connection from " . $ip."\n";

    $output = "Received following request:\n\n$data";

	switch($data) {
		#exit command will cause this script to quit out
        case 'exit';
            print "exit command received \n";
			exit(0);
		#hi command
		case 'hi';
            #write back to the client a response.
            yield \server_write($socket, "Hello {$ip}. This is our $i command run!");
			$i++;
			print "hi command received \n";
            break;
        default:
            $input = \explode(" ", $data);
            if (empty($input[1])) {
                $input[1] = "index.html";
            }
            $input = $input[1];
            $fileinfo = \pathinfo($input);
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

            $input = '.'.$input;

            $fd = yield \file_open($input, 'r');
            if (\is_resource($fd)) {
                print "Serving $input\n";

                if (\strstr($input, '.php')) {
                    $contents = yield \loadFile($input, [], $fd);
                } else {
                    $contents = yield \file_contents($fd);
                    yield \file_close($fd);
                }

                $output = \server_response($socket, $contents, 200);
            } else {
                $output = \server_response($socket, "The file you requested does not exist. Sorry!", 404);
            }

            yield \server_write($socket, $output);
    }

    yield \server_close($socket);
}

\coroutine_run(\server(5000));
