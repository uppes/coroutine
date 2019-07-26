<?php


namespace Async\Coroutine;

use Async\Coroutine\Kernel;

// use Psr\Http\Message\StreamInterface;
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
    public function head(string $url = null, ...$options);

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
     * {@inheritDoc}
     */
    public function getSize($stream = null): ?int;

    /**
     * {@inheritDoc}
     */
    public function tell(): int;

    /**
     * {@inheritDoc}
     */
    public function rewind(): void;

    /**
     * {@inheritDoc}
     */
    public function seek($offset, $whence = \SEEK_SET): void;
}
