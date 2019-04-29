<?php

namespace Async\Coroutine;

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;
//use Async\Coroutine\FileStreamInterface;

class FileStream //implements FileStreamInterface
{
    protected $socket;
    protected $handle;
    protected $meta = null;
    protected $isFile = false;
    protected $contents = null;

    public function __construct(string $uri, string $mode = 'r') 
	{
        $handle = \fopen($uri, $mode);
        
        if($handle !== false) {
            $this->isFile = true;
            \stream_set_blocking($handle, false);
            $this->meta = \stream_get_meta_data($handle);
        } else
            $this->isFiles = false;

        $this->handle = $handle;
    }

    public function contents(int $size, float $timeout_seconds = 0.5)
    {
        yield $this->stream_contents($size, $timeout_seconds);
    }

    private function stream_contents(int $size = 1, float $timeout_seconds = 0.5)
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

    public function fileExists()
    {
        return $this->isFile;
    }
}
