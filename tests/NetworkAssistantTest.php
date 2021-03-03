<?php

namespace Async\Tests;

use Async\Coroutine\NetworkAssistant;
use PHPUnit\Framework\TestCase;

class NetworkAssistantTest extends TestCase
{
    public function testResponse()
    {
        $parser = new NetworkAssistant('response');
        $response = "HTTP/1.1 200 OK" . \CRLF .
            "Date: " . \gmdate('D, d M Y H:i:s T') . CRLF .
            "Content-Type: text/html; charset=utf-8" . CRLF .
            "Content-Length: 5" . CRLF .
            "Server: Symplely Server" . CRLF .
            "X-Power: PHP" . CRLF .  CRLF .
            "hello";

        $this->assertEquals($response, $parser->response('hello', 200, null, ['x-power' => 'PHP']));

        $raw = <<<RAW
HTTP/1.1 200 OK
Date: Tue, 12 Apr 2016 13:58:01 GMT
Server: Apache/2.2.14 (Ubuntu)
X-Powered-By: PHP/5.3.14 ZendServer/5.0
Set-Cookie: ZDEDebuggerPresent=php,phtml,php3; path=/
Set-Cookie: PHPSESSID=6sf8fa8rlm8c44avk33hhcegt0; path=/; HttpOnly
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Vary: Accept-Encoding
Content-Encoding: gzip
Content-Length: 192
Content-Type: text/xml
RAW;
        $parser->parse($raw);
        $this->assertEquals('Tue, 12 Apr 2016 13:58:01 GMT', $parser->getHeader('Date'));
        $this->assertEquals('Apache/2.2.14 (Ubuntu)', $parser->getHeader('Server'));
        $this->assertEquals('PHP/5.3.14 ZendServer/5.0', $parser->getHeader('X-Powered-By'));
        $this->assertEquals('PHPSESSID=6sf8fa8rlm8c44avk33hhcegt0; path=/; HttpOnly', $parser->getHeader('Set-Cookie'));
        $this->assertEquals('Thu, 19 Nov 1981 08:52:00 GMT', $parser->getHeader('Expires'));
        $this->assertEquals('no-store, no-cache, must-revalidate, post-check=0, pre-check=0', $parser->getHeader('Cache-Control'));
        $this->assertEquals('no-cache', $parser->getHeader('Pragma'));
        $this->assertEquals('Accept-Encoding', $parser->getHeader('Vary'));
        $this->assertEquals('gzip', $parser->getHeader('Content-Encoding'));
        $this->assertEquals('192', $parser->getHeader('Content-Length'));
        $this->assertEquals('text/xml', $parser->getHeader('Content-Type'));
        $this->assertEquals('HTTP/1.1', $parser->getProtocol());
        $this->assertEquals('200', $parser->getCode());
        $this->assertEquals('OK', $parser->getMessage());

        $this->assertTrue($parser->hasHeader('Pragma'));
        $this->assertTrue($parser->hasFlag('Cache-Control', 'no-store'));
        $this->assertTrue($parser->hasVariable('Set-Cookie', 'PHPSESSID'));
        $this->assertEquals('/', $parser->getVariable('Set-Cookie', 'path'));

        $headers = $parser->getHeader();
        $this->assertCount(11, $headers);
    }

    public function testRequest()
    {
        $parser = new NetworkAssistant('request');
        $request = "GET /index.html HTTP/1.1" . CRLF .
            "Host: url.com" . CRLF .
            "Accept: */*" . CRLF .
            "X-Power: PHP" . CRLF .
            "User-Agent: Symplely Client" . CRLF .
            "Connection: close" . CRLF .  CRLF;

        $this->assertEquals($request, \net_request($parser, 'get', 'http://url.com/index.html', null, null, ['x-power' => 'PHP']));
        $this->assertEquals('http://url.com/index.html', $parser->getUri());

        $raw = <<<RAW
POST /path?free=one&open=two HTTP/1.1
User-Agent: PHP-SOAP/\BeSimple\SoapClient
Host: url.com:80
Accept: */*
Accept-Encoding: deflate, gzip
Content-Type:text/xml; charset=utf-8
Content-Length: 1108
Expect: 100-continue

<b>hello world</b>
RAW;

        $parser->parse($raw);
        $this->assertEquals('PHP-SOAP/\BeSimple\SoapClient', $parser->getHeader('User-Agent'));
        $this->assertEquals('url.com:80', $parser->getHeader('Host'));
        $this->assertEquals('*/*', $parser->getHeader('Accept'));
        $this->assertEquals('deflate, gzip', $parser->getHeader('Accept-Encoding'));
        $this->assertEquals('text/xml; charset=utf-8', $parser->getHeader('Content-Type'));
        $this->assertEquals('1108', $parser->getHeader('Content-Length'));
        $this->assertEquals('100-continue', $parser->getHeader('Expect'));

        $this->assertEquals('utf-8', $parser->getVariable('Content-Type', 'charset'));
        $this->assertEquals('', $parser->getVariable('Expect', 'charset'));
        $this->assertFalse($parser->hasHeader('Pragma'));
        $this->assertEquals('<b>hello world</b>', $parser->getBody());

        $default = $parser->getHeader('n/a');
        $this->assertEquals('', $default);

        $this->assertCount(2, $parser->getParameter());
        $this->assertEquals('one', $parser->getParameter('free'));
        $this->assertEquals('POST', $parser->getMethod());
        $this->assertEquals('/path', $parser->getPath());
        $this->assertEquals('HTTP/1.1', $parser->getProtocol());
    }
}
