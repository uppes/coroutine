<?php

namespace Async\Coroutine;

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;
//use Async\Coroutine\UriStreamInterface;

class UriStream //implements UriStreamInterface
{
    protected $handle = null;
    protected $meta = [];
    protected $host = '';
    protected $ip = null;
    protected $method = '';
    protected $status = 0;
    protected $isValid = false;
    protected $contents = '';

    public function __construct(string $url = null, string $mode = 'r', $isRequest = false) 
	{
        // assume default scheme if none has been given
        if (\strpos($url, '://') !== false) {
            // Explode out the parameters.
            $url_array = \explode("/", $url);
            // Is it http or https?
            $this->method = \strtolower(\array_shift($url_array));
            // Pop off an array blank.
            \array_shift($url_array);
            // Get the host.
            $this->host = \array_shift($url_array);
            $this->ip = \gethostbyname($this->host);
        } 

        if ($isRequest)
            $handle = \createClient("tcp://{$this->ip}:443", [], true);
        else
            $handle = \fopen($url, $mode);
        
        if (\is_resource($handle)) {
            $this->isValid = true;
            \stream_set_blocking($handle, false);
            $this->meta = \stream_get_meta_data($handle);
            $this->status = $this->getStatus($url);
            $this->handle = $handle;
        }
    }

    public function get(string $getPath = '/', $format = 'text/html')
    {
        $headers = "GET $getPath HTTP/1.1\r\n";
        $headers .= "Host: $this->host\r\n";
        $headers .= "Accept: */*\r\n";
        $headers .= "Content-type: $format; charset=utf8\r\n";
        $headers .= "Connection: close\r\n\r\n";
        return $this->waitWrite($headers);
    }

    public function post(string $path)
    {
        return $path;
    }

    public function update(string $path)
    {
        return $path;
    }

    public function delete(string $path)
    {
        return $path;
    }

    public function waitRead(int $size = 8192)
    {
        yield Call::waitForRead($this->handle);
        $response = \fread($this->handle, $size);
        yield;

        while (!\feof($this->handle)) {
            yield Call::waitForRead($this->handle);
            $response .= \fread($this->handle, $size);
            yield;
        }
        
        yield Coroutine::value($response);
    }

    public function waitWrite(string $string = null) 
	{
        yield Call::waitForWrite($this->handle);
        yield Coroutine::value(\fwrite($this->handle, $string));
    }
    
    public function contents(int $size = 1, float $timeout_seconds = 0.5)
    {
        $this->contents = '';
        while (true) {			
            $startTime = \microtime(true);
            $new = \stream_get_contents($this->handle, $size);
            $endTime = \microtime(true);
            if (\is_string($new) && \strlen($new) >= 1) {
                $this->contents .= $new;
            }
                
            $time_used = $endTime - $startTime;
            if (($time_used >= $timeout_seconds) 
                || ! \is_string($new) || (\is_string($new) && \strlen($new) < 1)) {
                break;
            }
            
            yield;
        }
    
        return $this->contents;
    }
    
    public function getContents()
    {
        return $this->contents;
    }

    public function isValid()
    {
        return $this->isValid;
    }

    public function getStatus(string $url = null) 
    {
        if (empty($url))
            return $this->status;

        $headers = @get_headers($url, true);
        $value = NULL;
        if ($headers === false) {
            return $headers;
        }
        foreach ($headers as $k => $v) {
            if (!is_int($k)) {
                continue;
            }
            $value = $v;
        }

        return (int) substr($value, strpos($value, ' ', 8) + 1, 3);
     }
}
