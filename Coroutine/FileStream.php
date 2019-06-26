<?php

declare(strict_types = 1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\SecureStreamSocket;
use Async\Coroutine\StreamSocketInterface;

class FileStream implements FileStreamInterface
{
    protected $resource;

    protected $meta = [];

    protected $isValid = false;

    protected static $instance = null;
    
    public function __construct()
	{
        self::$instance = $this;
    }

    public function fileOpen(string $uri = null, string $mode = 'r', $context = [])
	{
        $resource = null;
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

    public function fileContents(int $size = 256, float $timeout_seconds = 0.5)
    {
        yield;
        if (! \is_resource($this->resource))
            return false;

        $contents = '';
        while (true) {
            yield Kernel::readWait($this->resource);
            $startTime = \microtime(true);
            $new = \stream_get_contents($this->resource, $size);
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
    
        return $contents;
    }

    public function fileCreate($contents)
    {
        yield;
        if (! \is_resource($this->resource))
            return false;

        for ($written = 0; $written < \strlen($contents); $written += $fwrite) {
            yield Kernel::writeWait($this->resource);
            $fwrite = \fwrite($this->resource, \substr($contents, $written));
            // see https://www.php.net/manual/en/function.fwrite.php#96951
            if (($fwrite === false) || ($fwrite == 0)) {
                break;
            }
        }

        return $written;
    }

    public function fileLines()
    {
        yield;
        if (! \is_resource($this->resource))
            return false;

        $contents = [];
        while(! \feof($this->resource)) {
            yield Kernel::readWait($this->resource);
            $new = \fgets($this->resource);
            if (!empty($new))
                $contents[] = \trim($new, \EOL);
        }
    
        return $contents;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function fileMeta(): ?array
    {
        if (\is_resource($this->resource))
            $this->meta = \stream_get_meta_data($this->resource);

        return $this->meta;
    }

    public function fileStatus(array $meta = null) 
    {
        if (empty($meta))
            $meta = $this->meta;

        $result = array();
        $http_version = null;
        $http_statusCode = 400;
        $http_statusString = null;
        if (isset($meta['wrapper_data'])) {
            foreach ($meta['wrapper_data'] as $headerLine) {
                if (preg_match('/^HTTP\/(\d+\.\d+)\s+(\d+)\s*(.+)?$/', $headerLine, $result)) {
                    $http_version = $result[1];
                    $http_statusCode = $result[2];
                    $http_statusString = $result[3];
                }
            }
        }

        return (int) $http_statusCode;
    }

    public function fileValid(): bool
    {
        return $this->isValid;
    }

    public function fileHandle()
    {
        return $this->resource;
    }
    
    public static function instance(): FileStreamInterface
    {
        return self::$instance;
    }

    public function fileClose() 
	{
        $resource = $this->resource;
        $this->resource = null;
        $this->meta = null;

        if (\is_resource($resource))
            @\fclose($resource);
    }

    public static function input(int $size = 256, bool $error = false) 
	{
        //Check on STDIN stream
        $blocking = \stream_set_blocking(\STDIN, false);
        if ($error && !$blocking) {
            throw new \InvalidArgumentException('Non-blocking STDIN, could not be enabled.');
        }

        yield Kernel::readWait(\STDIN);
        $windows7 = \strpos(\php_uname('v'), 'Windows 7') !== false;
        // kinda of workaround to allow non blocking under Windows 10, if no key is typed, will block after key press
        if (!$blocking) {
            while(true) {
                $tell = \ftell(\STDIN);
                if (\is_int($tell) || $windows7)
                    break;
                else
                    yield;
            }
        }

		return \trim(\stream_get_line(\STDIN, $size, \EOL));
    }
}
