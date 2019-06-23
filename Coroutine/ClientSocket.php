<?php

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\SecureStreamSocket;
use Async\Coroutine\StreamSocketInterface;

class ClientSocket implements ClientSocketInterface
{
    protected $resource;
    protected $client;
    protected $meta = [];
    protected $host;
    protected $ip;
    protected $uri;
    protected $method;
    protected $port;
    protected $context;
    protected static $instance;
    
    private function __construct($client, $host = null) 
	{
        $this->resource = $client;
        $this->host = $host;
    }

    public static function create(string $uri = null, array $context = []) 
	{
        $url = $host = $uri;
        $isSSL = \array_key_exists('ssl', $context);
        if (\strpos($url, '://') !== false) {
            // Explode out the parameters.
            $url_array = \parse_url($url);
            // Is it http or https?
            $method = $url_array['scheme'];
            // Pop off an port.
            if (isset($url_array['port']))
                $port = $url_array['port'];

            if (empty($port))
                $port = ($method == 'https') || $isSSL ? 443 : 80;

            // Get the host.
            $host = $url_array['host'];
            //$ip = \gethostbyname($host);

            $url = "tcp://{$host}:$port";
        } elseif (\strpos($uri, '://') === false) {
            // Explode out the parameters.
            $url_array = \parse_url($url);
            // Pop off an port.
            if (isset($url_array['port']))
                $port = $url_array['port'];
                
            if (empty($port))
                $port = $isSSL ? 443 : 80;

            // Get the host.
            if (isset($url_array['host']))
                $host = $url_array['host'];

            //$ip = \gethostbyname($host);

            // assume default scheme if none has been given
            $url = 'tcp://' . $host. ':'.$port;
        }

        $ctx = \stream_context_create($context);
        if (($port == 443) || $isSSL) {
            \stream_context_set_option($ctx, "ssl", "allow_self_signed", true);
            \stream_context_set_option($ctx, "ssl", "disable_compression", true);
        }

        $client = @\stream_socket_client(
            $url, 
            $errNo,
            $errStr, 
            30, 
            \STREAM_CLIENT_ASYNC_CONNECT | \STREAM_CLIENT_CONNECT, 
            $ctx
        );

        if (!$client)
            throw new \RuntimeException(\sprintf('Failed to connect to %s: %s, %d', $uri, $errStr, $errNo));

        \stream_set_blocking($client, false);

        if (($port == 443) || $isSSL) {
            while (true) {
                yield Kernel::writeWait($client);
                $enabled = @\stream_socket_enable_crypto($client, true, \STREAM_CRYPTO_METHOD_SSLv23_CLIENT | \STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($enabled === false) 
                    throw new \RuntimeException(\sprintf('Failed to enable socket encryption: %s', \error_get_last()['message'] ?? ''));
                if ($enabled === true) 
                    break;
            }
        }

        self::$instance = new self($client, $host);
        return self::$instance;
    }

    public function meta(): ?array
    {
        if (\is_resource($this->resource))
            $this->meta = \stream_get_meta_data($this->resource);

        return $this->meta;
    }

    public function read(int $size = -1) 
	{
        if (!\is_resource($this->resource))
            return false;

        yield Kernel::readWait($this->resource);
        return \stream_get_contents($this->resource, $size);
    }

    public function write($string) 
	{
        if (!\is_resource($this->resource))
            return false;

        yield Kernel::writeWait($this->resource);
        return \fwrite($this->resource, $string);
    }

    public function close() 
	{
        $resource = $this->resource;
        $this->resource = null;
        $this->meta = null;
        $this->body = null;

        if (\is_resource($resource))
            @\fclose($resource);
    }

    public function valid(): bool
    {
        return \is_resource($this->resource);
    }

    public function handle(): ?\resource
    {
        return $this->resource;
    }
    
    public static function instance(): ClientSocketInterface
    {
        return self::$instance;
    }
}
