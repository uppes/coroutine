<?php
/**
 * This also an simpler version of 
 * "HOWTO: PHP TCP Server/Client with SSL Encryption using Streams"
 *  
 * @see http://blog.leenix.co.uk/2011/05/howto-php-tcp-serverclient-with-ssl.html
 */

include 'vendor/autoload.php';

// Let's ensure we have optimal performance. Set this simple thing
date_default_timezone_set('America/New_York');

error_reporting(-1);
ini_set("display_errors", 1);
$hostname = \gethostname();
$ip = \gethostbyname($hostname); //Set the TCP IP Address to connect too
$port="5000"; //Set the TCP PORT to connect too
//Command to run
if (isset($argc) && isset($argv[1])) {
    if ($argv[1] == '--host') {
        $hostname = $argv[2];
        $command = isset($argv[3]) ? $argv[3] : '/';
    } else {
        $hostname = $ip;
        $command=$argv[1];
    }
} else 
    $command="hi"; 

function client($hostname, $command) {
    global $argv;

    #Connect to Server
    #Start SSL
    $socket = yield \create_client("$hostname");

    if (isset($argv[1]) && ($argv[1] == '--host')) {
        $headers = "GET $command HTTP/1.1\r\n";
        
        $url_array = \parse_url($hostname);
        if (isset($url_array['host']))
            $hostname = $url_array['host'];

        $headers .= "Host: $hostname\r\n";
        $headers .= "Accept: */*\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "Connection: close\r\n\r\n";
        $http = $headers;
    } else 
        $http = $command;

    #Send a command
    yield \client_write($socket, $http);

    #Receive response from server. Loop until the response is finished
    $response = yield \client_read($socket);

    //\print_r(\client_meta($socket));

    #close connection
    yield \client_Close($socket);

    #echo our command response
    echo $response;
}

\coroutine_create(\client($hostname, $command));
\coroutine_run();
