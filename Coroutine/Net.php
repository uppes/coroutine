<?php

declare(strict_types=1);

//@todo add namespace Async\Net;

use Async\Coroutine\Network;
use Async\Coroutine\NetworkAssistant;

if (!\function_exists('net_operation')) {
    /**
     * Get the IP `address` corresponding to Internet host name.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $hostname
     * @param bool $useUv
     *
     * @return string|bool
     */
    function dns_address(string $hostname, bool $useUv = true)
    {
        return Network::address($hostname, $useUv);
    }

    /**
     * Get the Internet host `name` corresponding to IP address.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $ipAddress
     *
     * @return string|bool
     */
    function dns_name(string $ipAddress)
    {
        return Network::name($ipAddress);
    }

    /**
     * Get DNS Resource Records associated with hostname.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $hostname
     * @param int $options Constant type:
     * - `DNS_A` IPv4 Address Resource
     * - `DNS_CAA` Certification Authority Authorization Resource (available as of PHP 7.0.16 and 7.1.2)
     * - `DNS_MX` Mail Exchanger Resource
     * - `DNS_CNAME` Alias (Canonical Name) Resource
     * - `DNS_NS` Authoritative Name Server Resource
     * - `DNS_PTR` Pointer Resource
     * - `DNS_HINFO` Host Info Resource (See IANA Operating System Names for the meaning of these values)
     * - `DNS_SOA` Start of Authority Resource
     * - `DNS_TXT` Text Resource
     * - `DNS_ANY` Any Resource Record. On most systems this returns all resource records,
     * however it should not be counted upon for critical uses. Try DNS_ALL instead.
     * - `DNS_AAAA` IPv6 Address Resource
     * - `DNS_ALL` Iteratively query the name server for each available record type.
     *
     * @return array|bool
     */
    function dns_record(string $hostname, int $options = \DNS_A)
    {
        return Network::record($hostname, $options);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function listener_task(callable $handler)
    {
        return Network::listenerTask($handler);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_listen(
        $handle,
        int $handlerTask,
        int $backlog = 0,
        bool $isSecure = false,
        array $options = [],
        ?string $ssl_path = null,
        ?string $name = null,
        array $details = []
    ) {
        if (((string) (int) $handle === (string) $handle) || \is_string($handle)) {
            $handle = yield \net_server($handle, $isSecure, $options, $ssl_path, $name, $details);
        }

        return yield Network::listen($handle, $handlerTask, $backlog);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_client($uri = null, $optionsOrData = [])
    {
        return Network::client($uri, $optionsOrData);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_server(
        $uri = null,
        bool $isSecure = false,
        array $options = [],
        string $ssl_path = null,
        string $name = null,
        array $details = []
    ) {
        // Let's ensure we have optimal performance.
        \date_default_timezone_set('America/New_York');

        if ($isSecure)
            return Network::secure($uri, $options, $ssl_path, $name, $details);
        else
            return Network::server($uri, $options);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_stop(int $id)
    {
        return Network::stop($id);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_accept($handle)
    {
        return Network::accept($handle);
    }

    /**
     * - This function needs to be prefixed with `yield`
     *
     * @internal
     *
     * @codeCoverageIgnore
     */
    function net_connect(string $scheme, string $address, int $port, $data = null)
    {
        return Network::connect($scheme, $address, $port, $data);
    }

    /**
     * @internal
     *
     * @codeCoverageIgnore
     */
    function net_bind(string $scheme, string $address, int $port)
    {
        return Network::bind($scheme, $address, $port);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_read($handle, int $size = -1)
    {
        return Network::read($handle, $size);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_write($handle, string $response = '')
    {
        return Network::write($handle, $response);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_close($handle)
    {
        return Network::close($handle);
    }

    /**
     * Get the address of the connected handle.
     *
     * @param UVTcp|UVUdp|resource $handle
     * @return string|bool
     */
    function net_peer($handle)
    {
        return Network::peer($handle);
    }

    /**
     * Construct a new request string.
     *
     * @param object|NetworkAssistant $object
     * @param string $method
     * @param string $path
     * @param string|null $type
     * @param string|null $data
     * @param array $extra additional headers - associative array
     *
     * @return string
     */
    function net_request(
        $object,
        string $method = 'GET',
        string $path = '/',
        ?string $type = 'text/html',
        $data = null,
        array ...$extra
    ): string {
        if (
            $object instanceof NetworkAssistant
            || (\is_object($object) && \method_exists($object, 'request'))
        ) {
            return $object->request($method, $path, $type, $data, ...$extra);
        }
    }

    /**
     * Construct a new response string.
     *
     * @param object|NetworkAssistant $object
     * @param string $body defaults to `Not Found`, if empty and `$status`
     * @param int $status defaults to `404`, if empty and `$body`, otherwise `200`
     * @param string|null $type
     * @param array $extra additional headers - associative array
     *
     * @return string
     */
    function net_response(
        $object,
        ?string $body = null,
        ?int $status = null,
        ?string $type = 'text/html',
        array ...$extra
    ): string {
        if (
            $object instanceof NetworkAssistant
            || (\is_object($object) && \method_exists($object, 'response'))
        ) {
            return $object->response($body, $status, $type, ...$extra);
        }
    }

    /**
     * Turn `on/off` **libuv** for network operations.
     *
     * @param bool $useUV
     * - `true` use **libuv**, currently only *Linux* works correctly, this setting have no effect on *Windows*.
     * - `false` use PHP **builtin**.
     * * @param bool $reset
     *
     * `Note:` `libuv` network operations are currently broken on the *Windows* platform.
     */
    function net_operation(bool $useUV = false, $reset = false)
    {
        Network::setup($useUV, $reset);
    }
}
