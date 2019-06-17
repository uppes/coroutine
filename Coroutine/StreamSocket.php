<?php

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\SecureStreamSocket;
use Async\Coroutine\StreamSocketInterface;

class StreamSocket implements StreamSocketInterface
{
    protected $socket;
    protected $resource;
    protected $secure;
    protected $client;
    protected $instance;
    protected $meta = [];
    protected $host = '';
    protected $isValid = false;
    protected static $isClient = false;
    protected static $isSecure = false;
    protected static $remote = null;

    public function __construct($socket, bool $isClient = false, $host = null) 
	{
        $this->instance = $this;
        $this->socket = $socket;
        if ($isClient) {
            self::$isClient = true;
            $this->client = $socket;
            $this->host = $host;
        }
    }

    protected static function checkUri(array $parts = [], string $uri = '') 
    {
        if (empty($parts))
            $parts = \parse_url($uri);

        // ensure URI contains TCP scheme, host and port
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['port']) 
            || !\in_array($parts['scheme'], ['tcp', 'tls', 'http', 'https', 'ssl', 'udp', 'unix'])
        ) {
            throw new \InvalidArgumentException('Invalid URI "' . $uri . '" given');
		}
		
        if (false === \filter_var(\trim($parts['host'], '[]'), \FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Given URI "' . $uri . '" does not contain a valid host IP');
        }
    }

    public static function createClient(string $uri = null, array $context = [], bool $skipInterface = false) 
	{
        $url = $uri;
        if (\strpos($url, '://') !== false) {
            // Explode out the parameters.
            $url_array = \parse_url($url);
            // Is it http or https?
            $method = $url_array['scheme'];
            // Pop off an port.
            if (isset($url_array['port']))
                $port = $url_array['port'];

            if (empty($port))
                $port = ($method == 'https') || !empty($options) ? 443 : 80;

            // Get the host.
            $host = $url_array['host'];
            $ip = \gethostbyname($host);

            $url = "tcp://{$host}:$port";
        } elseif (\strpos($uri, '://') === false) {
            // assume default scheme if none has been given
            $url = 'tcp://' . $uri.(!empty($options) ? ':443' : ':80');
        }

        #Connect to Server
        $flag = empty($context) ? \STREAM_CLIENT_CONNECT : \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_ASYNC_CONNECT;
        $socket = @\stream_socket_client(
            $url, 
            $errNo,
            $errStr, 
            30, 
            $flag, 
            \stream_context_create($context)
        );

        if (!$socket)
            throw new \RuntimeException('Failed to connect to "' . $uri . '": ' . $errStr, $errNo);

        \stream_set_blocking($socket, false);        
        
        if (!empty($context)) {
            yield Kernel::writeWait($socket);
	        \stream_socket_enable_crypto ($socket, true, \STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }        

		yield Coroutine::value(($skipInterface === false) ? new self($socket, true, $host) : $socket);
    }

    /**
     * Creates a plaintext TCP/IP socket server and starts listening on the given address
     *
     * This starts accepting new incoming connections on the given address.
     * 
     * See the exception message and code for more details about the actual error
     * condition.
     *
     * Optionally, you can specify [socket context options](http://php.net/manual/en/context.socket.php),
     * their defaults and effects of changing these may vary depending on your system
     * and/or PHP version.
     * Passing unknown context options has no effect.
     *
     * @param string|int    $uri
     * @param array         $context
     * @throws InvalidArgumentException if the listening address is invalid
     * @throws RuntimeException if listening on this address fails (already in use etc.)
     */
    public static function createServer($uri = null, $context = []) 
	{
        $hostname = \gethostname();
        $ip = \gethostbyname($hostname);

        // a single port has been given => assume localhost
        if ((string)(int)$uri === (string)$uri) {
            $uri = $ip.':' . $uri;
        }

        // assume default scheme if none has been given
        if (\strpos($uri, '://') === false) {
            $uri = 'tcp://' . $uri;
        }
		
        // parse_url() does not accept null ports (random port assignment) => manually remove
        if (\substr($uri, -2) === ':0') {
            $parts = \parse_url(\substr($uri, 0, -2));
            if ($parts) {
                $parts['port'] = 0;
            }
        } else {
            $parts = \parse_url($uri);
		}
			
        self::checkUri($parts, $uri);
        
        if (empty($context))
            $context = \stream_context_create($context);

        #create a stream socket on IP:Port
        $socket = @\stream_socket_server(
            $uri, 
            $errNo, 
            $errStr,
            \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN, 
            $context
        );

        if (!$socket)
            throw new \RuntimeException('Failed to listen on "' . $uri . '": ' . $errStr, $errNo);
        
        print "Listening to {$uri} for connections\n";

		\stream_set_blocking($socket, false);

		return (self::$isSecure) ? $socket : new self($socket);
    }

    public function address()
    {
        return self::$remote;
    }    

    public function handshake()
	{
        \stream_set_blocking($this->socket, true);
        $this->secure  = $this->acceptConnection($this->socket);
        \stream_set_blocking($this->socket, false);
        return Coroutine::value(new SecureStreamSocket(SecureStreamSocket::acceptSecure($this->secure)));
    }

    public function accept()
	{
        yield Kernel::readWait($this->socket);
        if (self::$isSecure) {
            yield $this->handshake();
        } else
            yield Coroutine::value(new StreamSocket($this->acceptConnection($this->socket)));
    }

    protected function acceptConnection($socket)
	{
        $newSocket = \stream_socket_accept($socket, 0, self::$remote);

        if (false === $newSocket) {
            throw new \RuntimeException('Error accepting new connection');
        }

        return $newSocket;
    }

    public function fileOpen(string $uri = null, string $mode = 'r', $context = []) 
	{
        if (\in_array($mode, ['r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+']))
            $resource = @\fopen($uri, 
                $mode.'b', 
                false, 
                \is_resource($context) ? $context : \stream_context_create($context)
            );
        
        if (\is_resource($resource)) {
            $this->isValid = true;
            \stream_set_blocking($resource, false);
            $this->resource = $resource;
            $this->meta = $this->fileMeta($resource);
        }

        return $this->resource;
    }

    public function fileContents(int $size = 256, float $timeout_seconds = 0.5, $stream = null)
    {
        yield;
        $resource = empty($stream) ? $this->resource : $stream;

        if (! \is_resource($resource))
            return Coroutine::value(false);

        $contents = '';
        while (true) {
            yield Kernel::readWait($resource);
            $startTime = \microtime(true);
            $new = \stream_get_contents($resource, $size);
            $endTime = \microtime(true);
            if (\is_string($new) && \strlen($new) >= 1) {
                $contents .= $new;
            }
            
            $time_used = $endTime - $startTime;
            if (($time_used >= $timeout_seconds) 
                || ! \is_string($new) || (\is_string($new) && \strlen($new) < 1)) {
                break;
            }
        }
    
        yield Coroutine::value($contents);
    }

    public function fileCreate($contents, $stream = null)
    {
        yield;
        $resource = empty($stream) ? $this->resource : $stream;

        if (! \is_resource($resource))
            return Coroutine::value(false);

        for ($written = 0; $written < \strlen($contents); $written += $fwrite) {
            yield Kernel::writeWait($resource);
            $fwrite = \fwrite($resource, \substr($contents, $written));
            // see https://www.php.net/manual/en/function.fwrite.php#96951
            if (($fwrite === false) || ($fwrite == 0)) {
                break;
            }
        }

        yield Coroutine::value($written);
    }

    public function fileLines($stream = null)
    {
        $resource = empty($stream) ? $this->resource : $stream;

        if (! \is_resource($resource))
            return Coroutine::value(false);

        $contents = [];
        while(! \feof($resource)) {
            yield Kernel::readWait($resource);
            $new = \trim(\fgets($resource), \EOL);
            if (!empty($new))
                $contents[] = $new;
        }
    
        yield Coroutine::value($contents);
    }

    public function getMeta()
     {
        return $this->meta;
    }

    public function fileMeta($stream = null)
    {
        $check = empty($stream) ? $this->resource : $stream;

        if (empty($stream) && \is_resource($check))
            $this->meta = \stream_get_meta_data($check);
        elseif (\is_resource($check))
            return \stream_get_meta_data($check);

        return $this->meta;
    }

    public function fileValid(): bool
    {
        return $this->isValid;
    }

    public function fileHandle()
    {
        return $this->resource;
    }

    public static function input(int $size = 256, bool $error = false) 
	{
        //Check on STDIN stream
        $blocking = \stream_set_blocking(\STDIN, false);
        if ($error && !$blocking) {
            return new \InvalidArgumentException('Non-blocking STDIN, could not be enabled.');
        }
		yield Kernel::readWait(\STDIN);
		yield Coroutine::value(\trim(\stream_get_line(\STDIN, $size, \EOL)));
    }

    public function read(int $size = -1, $stream = null) 
	{
        $resource = empty($stream) ? $this->socket : $stream;

        yield Kernel::readWait($resource);
        yield Coroutine::value(\stream_get_contents($resource, $size));
        \stream_set_blocking($resource, false);
    }

    public function write(string $string, $stream = null) 
	{
        $resource = empty($stream) ? $this->socket : $stream;

        yield Kernel::writeWait($resource);
        yield Coroutine::value(\fwrite($resource, $string));
    }

    public function close() 
	{
        $this->fileClose($this->socket);
    }
    
    public function clientClose() 
	{
        $this->fileClose($this->client);
    }

    public function fileClose($stream = null) 
	{
        $resource = empty($stream) ? $this->resource : $stream;

        @\fclose($resource);
        if ($resource === $this->resource) {
            $this->resource = null;
            $this->meta = null;
        } elseif ($resource === $this->client) {
            self::$isClient = false;
            $this->client = null;
        } elseif ($resource === $this->socket) {
            $this->socket = null;
            $this->secure = null;
        }
    }
}
