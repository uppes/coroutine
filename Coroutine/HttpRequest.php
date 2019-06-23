<?php

declare(strict_types = 1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\HttpRequestInterface;
use Async\Coroutine\FileStreamInterface;

/**
 * Class HttpRequest
 * 
 * @package Async\Coroutine\HttpRequest
 */
class HttpRequest implements HttpRequestInterface
{
    /** 
     * @var string[] 
     */
    protected $protocolVersions = ["1.0", "1.1", "2.0"];

    /**
	 * The request method
     * 
     * @var string 
     */
    protected $method;

    /** 
	 * The requested uri
     * 
     * @var string 
     */
    protected $uri;

    /**
     * headers with lowercase keys
     * 
     * @var array  
     */
    protected $headers = [];

    /**
     * lowercase header to actual case map 
     * 
     * @var array 
     */
    protected $headerCaseMap = [];

    /**
     * Stream of data.
     *
     * @var resource|null
     */
    protected $resource;

    protected $instance;

    protected $meta = [];

	/**
	 * The request params
	 *
	 * @var array
	 */
    protected $parameters = [];
        
    protected function authorization(array $authorize)
    {
        $headers = '';
        if ($authorize['type'] =='basic' && !empty($authorize['username'])) {
            $headers .= "Authorization: Basic ";
            $headers .= \base64_encode($authorize['username'].':'.$authorize['password'])."\r\n";
        } elseif ($authorize['type']=='digest' && !empty($authorize['username'])) {
            $headers .= 'Authorization: Digest ';
            foreach ($authorize as $k => $v) {
                if (empty($k) || empty($v)) 
                    continue;
                if ($k=='password') 
                    continue;
                $headers .= $k.'="'.$v.'", ';
            }
            $headers .= "\r\n";
        }

        return $headers;
    }

    /**
     * @param string $method - GET, POST, HEAD, PUT, PATCH, DELETE
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize
     * @param string $format
     * @param string $charset
     * @param string $header
     * @param string $userAgent
     * @param float $protocolVersion
     * @param int $redirect
     * @param int $timeout
     * @return array|bool
     */
    protected function request(string $method = null, 
        string $url = null, 
        $data = null, 
        array $authorize = ['username' => "", 'password' => "", 'type' => ""], 
        string $format = null, 
        string $charset = 'utf-8', 
        string $header = null, 
        string $userAgent = 'Symplely Http', 
        float $protocolVersion = 1.1, 
        int $redirect = 20, 
        int $timeout = 60)
    {
        if (empty($url) || empty($format) 
            || !\in_array($method, ['CONNECT', 'DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT', 'TRACE'])
        )
            return false;

		// split uri and parameters string
		@list( $this->uri, $params ) = \explode( '?', $url );

        // parse the parameters
        if (!empty($params))
            \parse_str( $params, $this->parameters );
        
        $headers = $this->authorization($authorize);
        $contents = \is_array($data) ? \http_build_query($data) : $data;
        $length = !empty($contents) ? "Content-length: ".\strlen($contents)."\r\n" : '';
        $extra = !empty($header) ? "\r\n" : '';
        $context = [
            'http' =>
            [
                'method' => $method,
                'protocol_version' => $protocolVersion,
                'header' => $header.$extra.$headers."Content-type: {$format}; charset={$charset}\r\n{$length}Connection: close\r\n",
                'user_agent' => $userAgent,
                'max_redirects' => $redirect, // stop after 5 redirects
                'timeout' => $timeout, // timeout in seconds on response
            ]
        ];

        $context = \stream_context_create($context);

        if (!empty($contents))
            \stream_context_set_option($context, 'http', 'content', $contents);

        $this->instance = yield Kernel::fileOpen($url, 'r', $context);
        if ($this->instance instanceof FileStreamInterface) {
            $this->resource = $this->instance->fileHandle();
            if (\is_resource($this->resource)) {
                $this->meta = $this->instance->getMeta();
                if ($method == 'HEAD') {
                    $response = $this->getStatus();
                    $metaUpdated = false;
                } else {
                    $response = yield $this->getContents();
                    $metaUpdated = $this->getMetadata(null, $this->resource);
                }
                
                return [$this->meta, $response, $metaUpdated];            
            }
        }
        
        return false;
    }

    /**
     * @param string $url - URI for the request.
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param array $format - 'text/html'
     * @param string $charset - 'utf-8'
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @param int $redirect - 10
     * @param int $timeout - 30 
     * @return array|bool
     */
    public function get(string $url = null, ...$options)
    {
        if (empty($url))
            return false;

        $authorize = isset($options[0]) ? $options[0] : ['username' => "", 'password' => "", 'type' => ""];
        $format = isset($options[1]) ? $options[1] : 'text/html';
        $charset = isset($options[1]) ? $options[1] : 'utf-8'; 
        $userAgent = isset($options[2]) ? $options[2] : 'Symplely Http';
        $protocolVersion = isset($options[3]) ? $options[3] : 1.1;
        $redirect = isset($options[4]) ? $options[4] : 10;
        $timeout = isset($options[5]) ? $options[5] : 30;

        yield $this->request('GET', 
            $url, 
            null, 
            $authorize,
            $format, 
            $charset, 
            null, 
            $userAgent, 
            $protocolVersion, 
            $redirect, 
            $timeout
        );
    }

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param string $format - 'application/x-www-form-urlencoded'
     * @param string $charset - 'utf-8'
     * @param string $header
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @param int $redirect - 10
     * @param int $timeout - 30 
     * @return array|bool
     */
    public function post(string $url = null, $data = null, ...$options)
    {
        if (empty($url))
            return false;

        $authorize = isset($options[0]) ? $options[0] : ['username' => "", 'password' => "", 'type' => ""];
        $format = isset($options[1]) ? $options[1] : 'application/x-www-form-urlencoded';
        $charset = isset($options[1]) ? $options[1] : 'utf-8'; 
        $header = isset($options[2]) ? $options[2] : null;
        $userAgent = isset($options[3]) ? $options[3] : 'Symplely Http';
        $protocolVersion = isset($options[5]) ? $options[5] : 1.1;
        $redirect = isset($options[5]) ? $options[5] : 10;
        $timeout = isset($options[6]) ? $options[6] : 30;
        
        yield $this->request('POST', 
            $url, 
            $data, 
            $authorize, 
            $format, 
            $charset, 
            $header, 
            $userAgent, 
            $protocolVersion, 
            $redirect, 
            $timeout
        );
    }

    /**
     * @param string $url - URI for the request.
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @return array|bool
     */
    public function head(string $url = null, ...$options)
    {
        if (empty($url))
            return false;

        $authorize = isset($options[0]) ? $options[0] : ['username' => "", 'password' => "", 'type' => ""];
        $userAgent = isset($options[1]) ? $options[1] : 'Symplely Http';
        $protocolVersion = isset($options[2]) ? $options[2] : 1.1;

        $response = yield $this->request('HEAD', 
            $url, 
            null, 
            $authorize, 
            'text/html', 
            'utf-8', 
            null, 
            $userAgent, 
            $protocolVersion
        );

        if ($response === false) {
            if ($this->instance instanceof FileStreamInterface) {
                $this->resource = $this->instance->fileOpen($url);
                if (\is_resource($this->resource)) {
                    $this->meta = $this->instance->getMeta();
                    $response = [$this->meta, $this->getStatus(), true];
                }
            }
        }
        
        return $response;
    }

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param string $format - 'text/plain'
     * @param string $charset - 'utf-8'
     * @param string $header
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @param int $redirect - 10
     * @param int $timeout - 30 
     * @return array|bool
     */
    public function patch(string $url = null, $data = null, ...$options)
    {
        if (empty($url))
            return false;

        $authorize = isset($options[0]) ? $options[0] : ['username' => "", 'password' => "", 'type' => ""];
        $format = isset($options[1]) ? $options[1] : 'text/plain';
        $charset = isset($options[1]) ? $options[1] : 'utf-8'; 
        $header = isset($options[2]) ? $options[2] : null;
        $userAgent = isset($options[3]) ? $options[3] : 'Symplely Http';
        $protocolVersion = isset($options[5]) ? $options[5] : 1.1;
        $redirect = isset($options[5]) ? $options[5] : 10;
        $timeout = isset($options[6]) ? $options[6] : 30;

        yield $this->request('PATCH', 
            $url, 
            $data, 
            $authorize, 
            $format, 
            $charset, 
            $header, 
            $userAgent, 
            $protocolVersion, 
            $redirect, 
            $timeout
        );
    }

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param string $format - 'application/octet-stream'
     * @param string $charset - 'utf-8'
     * @param string $header
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @param int $redirect - 10
     * @param int $timeout - 30 
     * @return array|bool
     */
    public function put(string $url = null, $data = null, ...$options)
    {
        if (empty($url))
            return false;

        $authorize = isset($options[0]) ? $options[0] : ['username' => "", 'password' => "", 'type' => ""];
        $format = isset($options[1]) ? $options[1] : 'application/octet-stream';
        $charset = isset($options[1]) ? $options[1] : 'utf-8'; 
        $header = isset($options[2]) ? $options[2] : null;
        $userAgent = isset($options[3]) ? $options[3] : 'Symplely Http';
        $protocolVersion = isset($options[5]) ? $options[5] : 1.1;
        $redirect = isset($options[5]) ? $options[5] : 10;
        $timeout = isset($options[6]) ? $options[6] : 30;

        yield $this->request('PUT', 
            $url, 
            $data, 
            $authorize, 
            $format, 
            $charset, 
            $header, 
            $userAgent, 
            $protocolVersion, 
            $redirect, 
            $timeout
        );
    }

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param string $format - 'application/octet-stream'
     * @param string $charset - 'utf-8'
     * @param string $header
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @param int $redirect - 10
     * @param int $timeout - 30 
     * @return array|bool
     */
    public function delete(string $url = null, $data = null, ...$options)
    {
        if (empty($url))
            return false;

        $authorize = isset($options[0]) ? $options[0] : ['username' => "", 'password' => "", 'type' => ""];
        $format = isset($options[1]) ? $options[1] : 'application/octet-stream';
        $charset = isset($options[1]) ? $options[1] : 'utf-8'; 
        $header = isset($options[2]) ? $options[2] : null;
        $userAgent = isset($options[3]) ? $options[3] : 'Symplely Http';
        $protocolVersion = isset($options[5]) ? $options[5] : 1.1;
        $redirect = isset($options[5]) ? $options[5] : 10;
        $timeout = isset($options[6]) ? $options[6] : 30;

        yield $this->request('DELETE', 
            $url, 
            $data, 
            $authorize, 
            $format, 
            $charset, 
            $header, 
            $userAgent, 
            $protocolVersion, 
            $redirect, 
            $timeout
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        try {
            \fseek($this->resource, 0);
            return $this->getContents();
        } catch (\Exception $e) {
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $resource = $this->detach();
        \fclose($resource);
        $this->meta = null;
        if ($this->instance instanceof FileStreamInterface) {
            $this->instance->fileClose();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        unset($this->resource);
        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function eof($stream = null)
    {
        $handle = empty($stream) ? $this->resource : $stream;

        if (isset($handle)) {
            return \feof($handle);
        }

        return true;
    }

    public function getStatus($meta = null) 
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

    /**
     * {@inheritdoc}
     */
    public function getContents($stream = null)
    {
        $handle = empty($stream) ? $this->resource : $stream;
        
        if ($this->isReadable($handle)) {
            yield Kernel::readWait($handle);
            $contents = \stream_get_contents($handle);
            if (false !== $contents) {
                return $contents;
            }
            throw new \RuntimeException('Unable to get contents from underlying resource');
        }
        throw new \RuntimeException('Underlying resource is not readable');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null, $stream = null)
    {
        $handle = empty($stream) ? $this->resource : $stream;

        $metadata = \stream_get_meta_data($handle);
        if ($key) {
            $metadata = isset($metadata[$key]) ? $metadata[$key] : null;
        }
        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($stream = null)
    {
        $handle = empty($stream) ? $this->resource : $stream;

        if (!isset($handle)) {
            return false;
        }
        $mode = $this->getMetadata('mode');
        return \strstr($mode, 'r') || \strstr($mode, '+');
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $stream = null)
    {
        $handle = empty($stream) ? $this->resource : $stream;

        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable');
        }

        yield Kernel::readWait($handle);
        $contents = \stream_get_contents($handle, $length);
        \stream_set_blocking($handle, false);

        if (false !== $contents) {
            return $contents;
        }

        throw new \RuntimeException('Unable to read from underlying resource');
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($stream = null)
    {
        $handle = empty($stream) ? $this->resource : $stream;

        if (!isset($handle)) {
            return false;
        }
        $mode = $this->getMetadata('mode');
        return \strstr($mode, 'x')
            || \strstr($mode, 'w')
            || \strstr($mode, 'c')
            || \strstr($mode, 'a')
            || \strstr($mode, '+');
    }

    /**
     * {@inheritdoc}
     */
    public function write($string, $stream = null)
    {
        $handle = empty($stream) ? $this->resource : $stream;

        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable');
        }

        yield Kernel::writeWait($handle);
        $result = \fwrite($handle, $string);
        if (false !== $result) {
            return $result;
        }

        throw new \RuntimeException('Unable to write to underlying resource');
    }
        
    /**
     * Does the message contain the specified header field (case-insensitive)?
     *
     * @param string $field Header name.
     *
     * @return bool
     */
    public function hasHeader(string $field): bool 
    {
        return isset($this->headers[\strtolower($field)]);
    }

    /**
     * Retrieve the first occurrence of the specified header in the message.
     *
     * If multiple headers exist for the specified field only the value of the first header is returned. Applications
     * may use `getHeaderArray()` to retrieve a list of all header values received for a given field.
     *
     * A `null` return indicates the requested header field was not present.
     *
     * @param string $field Header name.
     *
     * @return string|null Header value or `null` if no header with name `$field` exists.
     */
    public function getHeader(string $field) 
    {
        return $this->headers[\strtolower($field)][0] ?? null;
    }

    /**
     * Retrieve all occurrences of the specified header in the message.
     *
     * Applications may use `getHeader()` to access only the first occurrence.
     *
     * @param string $field Header name.
     *
     * @return array Header values.
     */
    public function getHeaderArray(string $field): array 
    {
       return $this->headers[\strtolower($field)] ?? [];
    }

    /**
     * Assign a value for the specified header field by replacing any existing values for that field.
     *
     * @param string $field Header name.
     * @param string $value Header value.
     *
     * @return Request
     */
    public function withHeader(string $field, string $value): self 
    {
        $field = \trim($field);
        $lower = \strtolower($field);

        $this->headers[$lower] = [\trim($value)];
        $this->headerCaseMap[$lower] = $field;

        return $this;
    }

    public function withHeaders(array $headers): self 
    {
        foreach ($headers as $field => $values) {
            if (!\is_string($field) && !\is_int($field)) {
                // PHP converts integer strings automatically to integers.
                // Later versions of PHP might allow other key types.
                // @codeCoverageIgnoreStart
                throw new \TypeError("All array keys for withHeaders must be strings");
                // @codeCoverageIgnoreEnd
            }

            $field = \trim($field);
            $lower = \strtolower($field);

            if (!\is_array($values)) {
                $values = [$values];
            }

            $this->headers[$lower] = [];

            foreach ($values as $value) {
                if (!\is_string($value) && !\is_int($value) && !\is_float($value)) {
                    throw new \TypeError("All values for withHeaders must be string or an array of strings");
                }

                $this->headers[$lower][] = \trim($value);
            }

            $this->headerCaseMap[$lower] = $field;

            if (empty($this->headers[$lower])) {
                unset($this->headers[$lower], $this->headerCaseMap[$lower]);
            }
        }

        return $this;
    }

    /**
     * Retrieve an associative array of headers matching field names to an array of field values.
     *
     * @param bool $originalCase If true, headers are returned in the case of the last set header with that name.
     *
     * @return array
     */
    public function getHeaders(bool $originalCase = false): array 
    {
        if (!$originalCase) {
            return $this->headers;
        }

        $headers = [];

        foreach ($this->headers as $header => $values) {
            $headers[$this->headerCaseMap[$header]] = $values;
        }

        return $headers;
    }

    /**
     * Remove the specified header field from the message.
     *
     * @param string $field Header name.
     *
     * @return Request
     */
    public function withoutHeader(string $field): self 
    {
        $lower = \strtolower($field);

        unset(
            $this->headerCaseMap[$lower],
            $this->headers[$lower]
        );

        return $this;
    }
}
