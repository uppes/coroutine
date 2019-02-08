<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Coroutine;

use Async\Coroutine\Co;
use Async\Coroutine\Call;

class CoSocket 
{
    protected $socket;

    public function __construct($socket) 
	{
        $this->socket = $socket;
    }

    public function accept() 
	{
        yield Call::waitForRead($this->socket);
        yield Co::value(new CoSocket(\stream_socket_accept($this->socket, 0)));
    }
	
    public function read($size) 
	{
        yield Call::waitForRead($this->socket);
        yield Co::value(\fread($this->socket, $size));
    }

    public function write($string) 
	{
        yield Call::waitForWrite($this->socket);
        \fwrite($this->socket, $string);
    }

    public function close() 
	{
        @\fclose($this->socket);
    }
}
