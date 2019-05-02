<?php
/**
 * This also an simpler version of 
 * "HOWTO: PHP TCP Server/Client with SSL Encryption using Streams"
 *  
 * @see http://blog.leenix.co.uk/2011/05/howto-php-tcp-serverclient-with-ssl.html
 */

include 'vendor/autoload.php';

use Async\Coroutine\Coroutine;

// Let's ensure we have optimal performance. Set this simple thing
date_default_timezone_set('America/New_York');

error_reporting(-1);
ini_set("display_errors", 1);

$hostname = \gethostname();
$ip = \gethostbyname($hostname); //Set the TCP IP Address to connect too
$port="5000"; //Set the TCP PORT to connect too
$command="exit"; //Command to run

function client($ip, $port, $command) {    
    $contextOptions = array(
        'ssl' => array(                    
        'verify_peer' => false,
        'verify_peer_name' => false,
        'disable_compression' => true,
        'allow_self_signed' => true
        )
    );

    #Connect to Server
    #Start SSL
    $socket = \createClient("tcp://{$ip}:{$port}", $contextOptions);

    #Send a command
    yield \clientWrite($socket, $command);

    #Receive response from server. Loop until the response is finished
    yield \clientRead($socket);

    #close connection
    yield \closeClient($socket);

    #echo our command response
    echo $socket->getBuffer();
}

$coroutine = new Coroutine();
$coroutine->addTask(client($ip, $port, $command));
$coroutine->run();
