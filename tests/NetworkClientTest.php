<?php

namespace Async\Tests;

use function Async\Stream\{net_client, net_close, net_peer, net_read, net_write};

use Async\Coroutine\NetworkAssistant;
use PHPUnit\Framework\TestCase;

class NetworkClientTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function taskClient($hostname, $port = 80, $command = '/', $useSSL = true)
    {
        if ($useSSL) {
            $contextOptions = array(
                'ssl' => array(
                    'allow_self_signed' => true
                )
            );
        } else {
            $contextOptions = [];
        }

        #Connect to Server
        #Start SSL
        $resourceObject = yield net_client("$hostname:$port", $contextOptions);
        $this->assertTrue((\IS_WINDOWS || $useSSL || \IS_PHP8 ? \is_resource($resourceObject) : $resourceObject instanceof \UV));

        if ($resourceObject instanceof \UV) {
            $request = new NetworkAssistant('request', $hostname);
            $command = $request->request('get', $command);
        }

        #Send a command
        $written = yield net_write($resourceObject, $command);
        $this->assertEquals('int', \is_type($written));

        $remote = net_peer($resourceObject);
        $this->assertEquals('string', \is_type($remote));

        #Receive response from server. Loop until the response is finished
        $response = yield net_read($resourceObject);
        $this->assertEquals('string', \is_type($response));

        if ($resourceObject instanceof \UV) {
            $request->parse($response);
            $this->assertEquals('array', \is_type($request->getHeader('all')));
            $this->assertEquals('array', \is_type($request->getParameter('all')));
            $this->assertEquals('string', \is_type($request->getProtocol()));
            $this->assertEquals('int', \is_type($request->getCode()));
            $this->assertEquals('string', \is_type($request->getMessage()));
            $this->assertEquals('string', \is_type($request->getUri()));
        }

        #close connection
        yield net_close($resourceObject);
        $this->assertFalse(yield net_write($resourceObject));
        $this->assertFalse(yield net_read($resourceObject));
    }

    public function testClient()
    {
        \coroutine_run($this->taskClient('https://facebook.com', 443, '/'));
    }

    public function testClientAgain()
    {
        \coroutine_run($this->taskClient('msn.com', 80, '/', false));
    }
}
