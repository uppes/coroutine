<?php


namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\StreamSocketInterface;

// use Psr\Http\Message\StreamInterface;

/**
 * @param string $method - GET, POST, HEAD, PUT, PATCH, DELETE
 * @param string $url - URI for the request.
 * @param mixed $data
 * @param array $authorize
 * @param string $format
 * @param string $header
 * @param string $userAgent
 * @param float $protocolVersion
 * @param int $redirect
 * @param int $timeout
 * @return array|bool
 * 
 * protected function request(string $method = null, string $url = null, $data = null, array $authorize = ['username' => "", 'password' => "", 'type' => ""], string $format = null, string $header = null, string $userAgent = 'Symplely Http', float $protocolVersion = 1.1, int $redirect = 20, int $timeout = 60)
 * 
 */
// extends \Psr\Http\Message\StreamInterface

 interface HttpRequestInterface 
{
    /**
     * @param string $url - URI for the request.
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param array $format - 'text/html'
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @param int $redirect - 10
     * @param int $timeout - 30 
     * @return array|bool
     */
    public function get(string $url = null, ...$options);

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param string $format - 'application/x-www-form-urlencoded'
     * @param string $header
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @param int $redirect - 10
     * @param int $timeout - 30 
     * @return array|bool
     */
    public function post(string $url = null, $data = null, ...$options);

    /**
     * @param string $url - URI for the request.
     * @param array $authorize - ['username' => "", 'password' => "", 'type' => ""]
     * @param string $userAgent - 'Symplely Http'
     * @param float $protocolVersion - 1.1
     * @return array|bool
     */
    public function head(string $url, ...$options);

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize
     * @param string $format
     * @param string $header
     * @param string $userAgent
     * @param float $protocolVersion
     * @param int $redirect
     * @param int $timeout
     * @return array|bool
     */
    public function patch(string $url = null, $data = null, ...$options);

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize
     * @param string $format
     * @param string $header
     * @param string $userAgent
     * @param float $protocolVersion
     * @param int $redirect
     * @param int $timeout
     * @return array|bool
     */
    public function put(string $url = null, $data = null, ...$options);

    /**
     * @param string $url - URI for the request.
     * @param mixed $data
     * @param array $authorize
     * @param string $format
     * @param string $header
     * @param string $userAgent
     * @param float $protocolVersion
     * @param int $redirect
     * @param int $timeout
     * @return array|bool
     */
    public function delete(string $url = null, $data = null, ...$options);

    /**
     * {@inheritdoc}
     */
    public function close();

    /**
     * {@inheritdoc}
     */
    public function detach();

    /**
     * {@inheritdoc}
     */
    public function eof($stream = null);

    public function getStatus($meta = null);

    /**
     * {@inheritdoc}
     */
    public function getContents($stream = null);

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null, $stream = null);

    /**
     * {@inheritdoc}
     */
    public function isReadable($stream = null);

    /**
     * {@inheritdoc}
     */
    public function read($length, $stream = null);

    /**
     * {@inheritdoc}
     */
    public function isWritable($stream = null);

    /**
     * {@inheritdoc}
     */
    public function write($string, $stream = null);
        
    /**
     * Does the message contain the specified header field (case-insensitive)?
     *
     * @param string $field Header name.
     *
     * @return bool
     */
    public function hasHeader(string $field): bool;

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
    public function getHeader(string $field);

    /**
     * Retrieve all occurrences of the specified header in the message.
     *
     * Applications may use `getHeader()` to access only the first occurrence.
     *
     * @param string $field Header name.
     *
     * @return array Header values.
     */
    public function getHeaderArray(string $field);

    /**
     * Assign a value for the specified header field by replacing any existing values for that field.
     *
     * @param string $field Header name.
     * @param string $value Header value.
     *
     * @return Request
     */
    public function withHeader(string $field, string $value);

    public function withHeaders(array $headers);

    /**
     * Retrieve an associative array of headers matching field names to an array of field values.
     *
     * @param bool $originalCase If true, headers are returned in the case of the last set header with that name.
     *
     * @return array
     */
    public function getHeaders(bool $originalCase = false);

    /**
     * Remove the specified header field from the message.
     *
     * @param string $field Header name.
     *
     * @return Request
     */
    public function withoutHeader(string $field);
}
