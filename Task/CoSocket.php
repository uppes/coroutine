<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Async\Task;

use Async\Task\Co;
use Async\Task\Syscall;

class CoSocket 
{
    protected $socket;

    public function __construct($socket) 
	{
        $this->socket = $socket;
    }

    public function accept() 
	{
        yield Syscall::waitForRead($this->socket);
        yield Co::retval(new CoSocket(stream_socket_accept($this->socket, 0)));
    }
	
    public function read($size) 
	{
        yield Syscall::waitForRead($this->socket);
        yield Co::retval(fread($this->socket, $size));
    }

    public function write($string) 
	{
        yield Syscall::waitForWrite($this->socket);
        fwrite($this->socket, $string);
    }

    public function close() 
	{
        @fclose($this->socket);
    }
}
