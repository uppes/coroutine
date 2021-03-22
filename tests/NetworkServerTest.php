<?php

namespace Async\Tests;

use function Async\Stream\{
    listener_task,
    net_accept,
    net_client,
    net_close,
    net_listen,
    net_operation,
    net_local,
    net_read,
    net_response,
    net_server,
    net_stop,
    net_write
};

use Async\Coroutine\NetworkAssistant;
use PHPUnit\Framework\TestCase;

class NetworkServerTest extends TestCase
{
    protected $loopController = true;
    protected $taskId = null;

    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function taskSecureServer($port)
    {
        if (\IS_LINUX) {
            net_operation();
            $this->expectOutputRegex('/[Listening to ' . $port . 'for connections]/');
        }

        $resourceInstance = yield net_server($port, true);
        $this->assertTrue(\is_resource($resourceInstance));

        $ip = net_local($resourceInstance);
        $this->assertEquals('string', \is_type($ip));

        // Will connection to this server in .005 seconds.
        yield \away($this->taskFakeSecureClientCommand($port));

        if (\IS_WINDOWS) {
            $this->expectOutputRegex('/[Connection lost during TLS handshake with: ' . $port . ']/');
        }

        // Will pause current task and wait for connection, all others tasks will continue to run
        $connected = yield net_accept($resourceInstance);
        $this->assertTrue(\is_resource($connected));

        yield net_close($resourceInstance);
        yield \shutdown();
    }

    public function taskFakeSecureClientCommand($port)
    {
        yield \sleep_for(.005);
        #Connect to Server
        $clientInstance = yield net_client($port);
        yield net_close($clientInstance);
    }

    public function taskListen($resourceInstance)
    {
        $this->assertTrue((\IS_WINDOWS ? \is_resource($resourceInstance) : $resourceInstance instanceof \UV));
        yield net_stop($this->taskId);
    }

    public function taskServerListen($port)
    {
        $lid = yield listener_task([$this, 'taskListen']);
        $this->taskId = $lid;
        $this->expectOutputRegex('/[Listening to ' . $port . 'for connections]/');
        // Will connection to this server in .005 seconds.
        $dit = yield \away($this->taskFakeSecureClientCommand($port));
        yield net_listen($port, $lid);
        yield \shutdown();
    }

    public function taskServer($port)
    {
        \error_reporting(-1);
        \ini_set("display_errors", 1);

        $this->expectOutputRegex('/[Listening to ' . $port . 'for connections]/');
        $serverInstance = yield net_server($port);

        $this->assertTrue((\IS_WINDOWS ? \is_resource($serverInstance) : $serverInstance instanceof \UV));

        $fakeClientSkipped = false;
        while ($this->loopController) {
            if (!$fakeClientSkipped) {
                $fakeClientSkipped = true;
                // Will connection to this server in .005 seconds.
                yield \away($this->taskFakeClientCommand($port));
            }

            // Will pause current task and wait for connection, all others tasks will continue to run
            $connectedServer = yield net_accept($serverInstance);
            $this->assertTrue((\IS_WINDOWS ? \is_resource($connectedServer) : $connectedServer instanceof \UV));
            // Once an connection is made, will create new task and continue execution there, will not block
            yield \away($this->taskHandleClient($connectedServer));
        }

        yield net_close($serverInstance);
        yield \shutdown();
    }

    public function taskFakeClientCommand($port)
    {
        yield \sleep_for(.005);
        #Connect to Server
        $clientInstance = yield net_client($port);
        #Send a command
        yield net_write($clientInstance, 'hi');
        #Receive response from server. Loop until the response is finished
        $response = yield net_read($clientInstance);
        $this->assertEquals('Hello, This is our command run!', $response);
        yield net_close($clientInstance);
        // make an new client connection to this server.
        yield \away($this->taskFakeClientDefault($port));
    }

    public function taskFakeClientDefault($port)
    {
        #Connect to Server
        $clientInstance = yield net_client($port);
        #Send a command
        yield net_write($clientInstance, 'help');
        #Receive response from server. Loop until the response is finished
        $response = yield net_read($clientInstance);
        $this->assertEquals('string', \is_type($response));
        $this->assertRegExp('/[The file you requested does not exist. Sorry!]/', $response);
        yield net_close($clientInstance);
        // make an new client connection to this server.
        yield \away($this->taskFakeClientExit($port));
    }

    public function taskFakeClientExit($port)
    {
        $this->loopController = false;
        #Connect to Server
        $clientInstance = yield net_client($port);
        #Send a command
        yield net_write($clientInstance, 'exit');
        yield net_close($clientInstance);
    }

    public function taskHandleClient($server)
    {
        yield \stateless_task();
        $data = yield net_read($server);
        $this->assertEquals('string', \is_type($data));

        switch ($data) {
                #exit command will cause this script to quit out
            case 'exit';
                print "exit command received \n";
                $this->loopController = false;
                break;
                #hi command
            case 'hi';
                #write back to the client a response.
                $written = yield net_write($server, 'Hello, This is our command run!');
                $this->assertEquals('int', \is_type($written));
                print "hi command received \n";
                break;
            default:
                $responser = new NetworkAssistant('response');
                $output = net_response($responser, 'The file you requested does not exist. Sorry!', 404);
                $this->assertEquals('string', \is_type($output));
                yield net_write($server, $output);
        }

        yield net_close($server);
        $this->assertFalse(yield net_write($server, 'null'));
        $this->assertFalse(yield net_read($server));
    }

    public function testServer()
    {
        \coroutine_run($this->taskServer(9999));
    }

    public function testSecureServer()
    {
        \coroutine_run($this->taskSecureServer(9990));
    }

    public function testServerListen()
    {
        \coroutine_run($this->taskServerListen(9998));
    }
}
