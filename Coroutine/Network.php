<?php

declare(strict_types=1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoroutineInterface;

/**
 * A general purpose networking class where the method is directed
 * by the scheme used, and either a **libuv** object
 * or built-in **native** resource.
 */
final class Network
{
    /**
     * Flag to control `UV` network operations.
     *
     * @var bool
     */
    protected static $useUV = true;

    protected static $caPath = \DS;
    protected static $privatekey = 'privatekey.pem';
    protected static $certificate = 'certificate.crt';
    protected static $method = null;
    protected static $isRunning = [];
    protected static $isSecure = false;

    /**
     * Check for `libuv` for network operations.
     *
     * @return bool
     */
    protected static function isUv(): bool
    {
        $co = \coroutine_instance();
        return ($co instanceof CoroutineInterface && $co->isUvActive() && self::$useUV);
    }

    /**
     * Setup how **Coroutine** handle network operations.
     *
     * @param bool $useUV
     * - `true` on - will use `libuv` features.
     * - `false` off - will use `PHP` native builtin routines.
     * @param bool $reset
     */
    public static function setup(bool $useUV = true, $reset = false)
    {
        self::$useUV = $useUV;
        if ($reset) {
            self::$isSecure = false;
            self::$privatekey = 'privatekey.pem';
            self::$certificate = 'certificate.crt';
            self::$method = null;
            self::$isRunning = [];
        }
    }

    /**
     * Stop the network server listening for new connection on specified listener Task ID.
     * - This function needs to be prefixed with `yield`
     *
     * @param int $listener Task ID
     */
    public static function stop(int $listener)
    {
        self::$isRunning[$listener] = false;
        $co = \coroutine_instance();
        yield self::close($co->taskInstance($listener)->getCustomData());
        try {
            yield \cancel_task($listener);
        } catch (\Throwable $e) {
        }
    }

    /**
     * Get the IP `address` corresponding to Internet host name.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $hostname
     * @param bool $useUv
     */
    public static function address(string $hostname, bool $useUv = true)
    {
        if (!\filter_var($hostname, \FILTER_VALIDATE_DOMAIN, \FILTER_FLAG_HOSTNAME)) {
            return \result(false);
        }

        $co = \coroutine_instance();
        if ($co instanceof CoroutineInterface && $co->isUv() && $useUv) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($hostname) {
                    $coroutine->ioAdd();
                    \uv_getaddrinfo(
                        $coroutine->getUV(),
                        function (int $status, $result) use ($task, $coroutine) {
                            $coroutine->ioRemove();
                            $task->sendValue(($status < 0 ? false : $result[0]));
                            $coroutine->schedule($task);
                        },
                        $hostname,
                        '',
                        [
                            "ai_family" => \UV::AF_UNSPEC
                        ]
                    );
                }
            );
        }

        return \spawn_system('gethostbyname', $hostname);
    }

    /**
     * Get the Internet host `name` corresponding to IP address.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $ipAddress
     *
     * @return string|bool
     */
    public static function name(string $ipAddress)
    {
        if (!\filter_var($ipAddress, \FILTER_VALIDATE_IP)) {
            return \result(false);
        }

        return \spawn_system('gethostbyaddr', $ipAddress);
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
    public static function record(string $hostname, int $options = \DNS_A)
    {
        if (!\filter_var($hostname, \FILTER_VALIDATE_DOMAIN, \FILTER_FLAG_HOSTNAME)) {
            return \result(false);
        }

        return \spawn_system('dns_get_record', $hostname, $options);
    }

    /**
     * Add a listener handler for the network server, that's continuously monitored.
     * This function will return `int` immediately, use with `network_listen()`.
     * - The `$handler` function will be executed every time on new incoming connections or data.
     * - Expect the `$handler` to receive `(resource|\UVStream $newConnection)`.
     *
     *  Or
     * - Expect the `$handler` to receive `($data)`. If  `UVUdp`
     * - This function needs to be prefixed with `yield`
     *
     * @param callable $handler
     *
     * @return int
     *
     * @codeCoverageIgnore
     */
    public static function listenerTask(callable $handler)
    {
        return Kernel::away(function () use ($handler) {
            $co = \coroutine_instance();
            $tid = yield \stateless_task();
            self::$isRunning[$tid] = true;
            while (self::$isRunning[$tid]) {
                $received = yield;
                if (\is_array($received) && (\count($received) == 3 && $received[0] == $tid)) {
                    [$nan, $callerID, $clientConnectionOrData] = $received;
                    $received = null;
                    $newId = yield \away(function () use ($handler, $clientConnectionOrData) {
                        return yield $handler($clientConnectionOrData);
                    });

                    $co->taskInstance($newId)->taskType('networked');
                }
            }

            if ($co->taskInstance($callerID) instanceof TaskInterface) {
                $co->ioRemove();
                $task = $co->taskInstance($callerID);
                $task->sendValue(true);
                $co->schedule($task);
            }
        });
    }

    /**
     * @param resource|\UVTcp|\UVUdp|\UVPipe $server
     * @param int $listenerTask
     * @param int $backlog
     */
    public static function listen($server, int $listenerTask, int $backlog = 0)
    {
        if (self::isUv() && ($server instanceof \UVStream || $server instanceof \UVUdp)) {
            return yield new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($server, $listenerTask, $backlog) {
                    $task->customData($server);
                    $task->taskType('networked');
                    $coroutine->ioAdd();
                    if ($server instanceof \UVUdp) {
                        \uv_udp_recv_start($server, function ($stream, $status, $data) use ($task, $coroutine, $listenerTask) {
                            $listen = $coroutine->taskInstance($listenerTask);
                            $listen->customData($stream);
                            $listen->sendValue([$listenerTask, $task->taskId(), $data]);
                            $coroutine->schedule($listen);
                        });
                    } else {
                        $backlog = empty($backlog) ? ($server instanceof \UVTcp ? 1024 : 8192) : $backlog;
                        \uv_listen($server, $backlog, function ($server, $status) use ($task, $coroutine, $listenerTask) {
                            $listen = $coroutine->taskInstance($listenerTask);
                            $uv = $coroutine->getUV();
                            if ($server instanceof \UVTcp) {
                                $client = \uv_tcp_init($uv);
                            } elseif ($server instanceof \UVPipe) {
                                if (\IS_WINDOWS)
                                    $client = \uv_pipe_init($uv, true);
                                else
                                    $client = \uv_pipe_init($uv, false);
                            }

                            \uv_accept($server, $client);
                            $listen->customData($client);
                            $listen->sendValue([$listenerTask, $task->taskId(), $client]);
                            $coroutine->schedule($listen);
                        });
                    }
                }
            );
        }

        if (!\is_resource($server))
            return false;

        while (true) {
            $client = yield self::accept($server);
            yield self::listening($client, $listenerTask);
            $isTrue = yield;
            if ($isTrue === true) {
                break;
            }
        }

        return $isTrue;
    }

    protected static function listening($client, int $listenerTask)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($client, $listenerTask) {
                $task->customData($client);
                $task->taskType('networked');
                $listen = $coroutine->taskInstance($listenerTask);
                $listen->sendValue([$listenerTask, $task->taskId(), $client]);
                $coroutine->schedule($listen);
                $coroutine->schedule($task);
            }
        );
    }

    /**
     * @param resource|\UVTcp|\UVTty|\UVPipe $handle
     * @param int $size
     *
     * @return string|bool
     */
    public static function read($handle, $size = -1)
    {
        if (self::isUv() && $handle instanceof \UV) {
            return yield new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($handle) {
                    if (!\uv_is_closing($handle)) {
                        $coroutine->ioAdd();
                        \uv_read_start(
                            $handle,
                            function ($handle, $nRead, $data) use ($task, $coroutine) {
                                if ($nRead > 0) {
                                    $coroutine->ioRemove();
                                    $task->sendValue($data);
                                    $coroutine->schedule($task);
                                    \uv_read_stop($handle);
                                }
                            }
                        );
                    } else {
                        $task->sendValue(false);
                        $coroutine->schedule($task);
                    }
                }
            );
        }

        if (!\is_resource($handle))
            return false;

        yield Kernel::readWait($handle);
        \stream_set_blocking($handle, false);
        yield Coroutine::value(\stream_get_contents($handle, $size));
    }

    /**
     * @param resource|\UV $handle
     * @param mixed $data
     *
     * @return int|bool
     */
    public static function write($handle, $data = '')
    {
        if (self::isUv() && $handle instanceof \UV) {
            return yield new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($handle, $data) {
                    if (!\uv_is_closing($handle)) {
                        $coroutine->ioAdd();
                        \uv_write(
                            $handle,
                            $data,
                            function ($handle, $status) use ($task, $coroutine) {
                                $coroutine->ioRemove();
                                $task->sendValue($status);
                                $coroutine->schedule($task);
                            }
                        );
                    } else {
                        $task->sendValue(false);
                        $coroutine->schedule($task);
                    }
                }
            );
        }

        if (!\is_resource($handle))
            return false;

        yield Kernel::writeWait($handle);
        yield Coroutine::value(\fwrite($handle, $data));
    }

    /**
     * @param resource|\UV $handle
     *
     * @return bool
     */
    public static function close($handle)
    {
        if ($handle instanceof \UV) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($handle) {
                    $coroutine->ioAdd();
                    \uv_close(
                        $handle,
                        function ($handle, $status = null) use ($task, $coroutine) {
                            $coroutine->ioRemove();
                            $task->sendValue($status);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        if (\is_resource($handle))
            return @\fclose($handle);

        return false;
    }

    /**
     * @param string|null|int $uri
     * @param array|mixed $contextOrData
     *
     * @return resource|\UVTcp|\UVUdp|\UVPipe
     */
    public static function client($uri = null, $contextOrData = [])
    {
        if (\is_array($contextOrData))
            $isSSL = \array_key_exists('ssl', $contextOrData);

        [$parts, $uri] = yield self::makeUri($uri);

        $port = $parts['port'];
        if (empty($port))
            $port = (($parts['scheme'] == 'https') || $isSSL) ? 443 : 80;

        if (self::isUv() && !$isSSL) {
            $isPipe = ($parts['scheme'] == 'file') || ($parts['scheme'] == 'unix');
            $address = $isPipe ? $parts['host'] : yield \dns_address($parts['host']);
            $client = yield self::connect($parts['scheme'], $address, $port, $contextOrData);
        } else {
            $ctx = \stream_context_create($contextOrData);
            if (($port == 443) || $isSSL) {
                \stream_context_set_option($ctx, 'ssl', 'ciphers', 'HIGH:!SSLv2:!SSLv3');
                \stream_context_set_option($ctx, 'ssl', 'allow_self_signed', true);
                \stream_context_set_option($ctx, 'ssl', 'disable_compression', true);
                $mask = \STREAM_CLIENT_ASYNC_CONNECT | \STREAM_CLIENT_CONNECT;
            } else {
                $mask = \STREAM_CLIENT_CONNECT;
            }

            $client = @\stream_socket_client(
                $uri,
                $errNo,
                $errStr,
                1,
                $mask,
                $ctx
            );

            if (\is_resource($client)) {
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
            }
        }

        if (!$client) {
            if (isset($errStr))
                throw new \RuntimeException(\sprintf('Failed to connect to %s: %s, %d', $uri, $errStr, $errNo));
            else
                throw new \RuntimeException(\sprintf('Failed to connect to: %s', $uri));
        }

        return $client;
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
     * @param string|null|int $uri
     * @param array $context
     * @throws InvalidArgumentException if the listening address is invalid
     * @throws RuntimeException if listening on this address fails (already in use etc.)
     *
     * @return resource|\UVTcp|\UVUdp|\UVPipe
     */
    public static function server($uri = null, $context = [])
    {
        [$parts, $uri] = yield self::makeUri($uri);

        if (self::isUv() && !self::$isSecure) {
            $isPipe = ($parts['scheme'] == 'file') || ($parts['scheme'] == 'unix');
            $address = $isPipe ? $parts['host'] : yield \dns_address($parts['host']);
            $server = self::bind($parts['scheme'], $address, $parts['port']);
        } else {
            if (empty($context))
                $context = \stream_context_create($context);

            #create a stream server on IP:Port
            $server = \stream_socket_server(
                $uri,
                $errNo,
                $errStr,
                \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN,
                $context
            );

            \stream_set_blocking($server, false);
        }

        if (!$server) {
            if (isset($errStr))
                throw new \RuntimeException('Failed to listen on "' . $uri . '": ' . $errStr, $errNo);
            else
                throw new \RuntimeException('Failed to listen on "' . $uri);
        }

        print "Listening to {$uri} for connections\n";

        return $server;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function bind(string $scheme, string $address, int $port)
    {
        $ip = \uv_ip6_addr($address, $port);
        if (\strpos($address, ':') === false)
            $ip = \uv_ip4_addr($address, $port);

        $uv = \coroutine_instance()->getUV();
        switch ($scheme) {
            case 'file':
            case 'unix':
                if (\IS_WINDOWS)
                    $handle = \uv_pipe_init($uv, true);
                else
                    $handle = \uv_pipe_init($uv, false);

                \uv_pipe_bind($handle, $address);
                break;
            case 'udp':
                $handle = \uv_udp_init($uv);
                \uv_udp_bind($handle, $ip);
                break;
            case 'tcp':
            case 'tls':
            case 'ftp':
            case 'ftps':
            case 'ssl':
            case 'http':
            case 'https':
            default:
                $handle = \uv_tcp_init($uv);
                \uv_tcp_bind($handle, $ip);
                break;
        }

        return $handle;
    }

    public static function accept($server)
    {
        if (self::isUv() && $server instanceof \UV) {
            return yield new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($server) {
                    $task->customData($server);
                    $task->taskType('networked');
                    $coroutine->ioAdd();
                    if ($server instanceof \UVUdp) {
                        \uv_udp_recv_start($server, function ($stream, $status, $data) use ($task, $coroutine) {
                            $task->customData($stream);
                            $task->sendValue($data);
                            $coroutine->ioRemove();
                            $coroutine->schedule($task);
                        });
                    } else {
                        $backlog = $server instanceof \UVTcp ? 1024 : 8192;
                        \uv_listen($server, $backlog, function ($server, $status) use ($task, $coroutine) {
                            $coroutine->ioRemove();
                            $uv = $coroutine->getUV();
                            if ($server instanceof \UVTcp) {
                                $client = \uv_tcp_init($uv);
                            } elseif ($server instanceof \UVPipe) {
                                if (\IS_WINDOWS)
                                    $client = \uv_pipe_init($uv, true);
                                else
                                    $client = \uv_pipe_init($uv, false);
                            }

                            \uv_accept($server, $client);
                            $task->customData($server);
                            $task->sendValue($client);
                            $coroutine->ioRemove();
                            $coroutine->schedule($task);
                        });
                    }
                }
            );
        }

        if (!\is_resource($server))
            return false;

        yield \stateless_task();
        yield Kernel::readWait($server);
        if (self::$isSecure) {
            yield self::handshake($server);
            \stream_set_blocking($server, false);
        } else
            yield Coroutine::value(self::accepting($server));
    }

    protected static function accepting($handle)
    {
        $client = \stream_socket_accept($handle, 0);
        if (false === $client) {
            throw new \RuntimeException('Error accepting new connection');
        }

        return $client;
    }

    protected static function handshake($handle)
    {
        \stream_set_blocking($handle, true);
        $secure = self::accepting($handle);
        \stream_set_blocking($handle, false);

        $error = null;
        \set_error_handler(function ($_, $errstr) use (&$error) {
            $error = \str_replace(array("\r", "\n"), ' ', $errstr);
            // remove useless function name from error message
            if (($pos = \strpos($error, "): ")) !== false) {
                $error = \substr($error, $pos + 3);
            }
        });

        \stream_set_blocking($secure, true);
        $result = @\stream_socket_enable_crypto($secure, true, self::$method);

        \restore_error_handler();

        if (false === $result) {
            if (\feof($secure) || $error === null) {
                // EOF or failed without error => connection closed during handshake
                \printf("Connection lost during TLS handshake with: %s\n", \stream_socket_get_name($secure, true));
            } else {
                // handshake failed with error message
                \printf("Unable to complete TLS handshake: %s\n", $error);
            }
        }

        return Coroutine::value($secure);
    }

    /**
     * Get the address of the connected handle.
     *
     * @param UVTcp|UVUdp|resource $handle
     * @return string|bool
     */
    public static function peer($handle)
    {
        if ($handle instanceof \UVTcp) {
            $peer = \uv_tcp_getpeername($handle);
            return $peer['address'] . ':' . $peer['port'];
        } elseif ($handle instanceof \UVUdp) {
            $peer = \uv_udp_getsockname($handle);
            return $peer['address'] . ':' . $peer['port'];
        }

        if (!\is_resource($handle))
            return false;

        return \stream_socket_get_name($handle, true);
    }

    public static function connect(string $scheme, string $address, int $port, $data = null)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($scheme, $address, $port, $data) {
                $callback =  function ($client, $status) use ($task, $coroutine) {
                    $coroutine->ioRemove();
                    if (\is_int($status))
                        $task->sendValue(($status < 0 ? false : $client));
                    else
                        $task->sendValue(($client < 0 ? false : $status));

                    $coroutine->schedule($task);
                };

                $coroutine->ioAdd();
                $uv = $coroutine->getUV();
                $ip = @\uv_ip6_addr($address, $port);
                if (\strpos($address, ':') === false)
                    $ip = @\uv_ip4_addr($address, $port);

                switch ($scheme) {
                    case 'file':
                    case 'unix':
                        if (\IS_WINDOWS)
                            $client = \uv_pipe_init($uv, true);
                        else
                            $client = \uv_pipe_init($uv, false);

                        \uv_pipe_connect($client, $address, $callback);
                        break;
                    case 'udp':
                        $client = \uv_udp_init($uv);
                        \uv_udp_send($client, $data, $ip, $callback);
                        break;
                    case 'tcp':
                    case 'tls':
                    case 'ftp':
                    case 'ftps':
                    case 'ssl':
                    case 'http':
                    case 'https':
                    default:
                        $client = \uv_tcp_init($uv);
                        \uv_tcp_connect($client, $ip, $callback);
                        break;
                }
            }
        );
    }

    /**
     * Creates a secure TCP/IP socket server and starts listening on the given address
     *
     * This starts accepting new incoming connections on the given address.
     *
     * See the exception message and code for more details about the actual error
     * condition.
     *
     * @param string|null|int $uri
     * @param array $options - specify socket context options, their defaults and effects of changing these may vary
     * depending on your system and/or PHP version. Passing unknown context options has no effect.
     * @param string $ssl_path
     * @param array $details - certificate details
     *
     * Example:
     * ````
     *  $details = [
     *      "countryName" =>  '',
     *      "stateOrProvinceName" => '',
     *      "localityName" => '',
     *      "organizationName" => '',
     *      "organizationalUnitName" => '',
     *      "commonName" => '',
     *      "emailAddress" => ''
     *  ];
     * ````
     * @throws InvalidArgumentException if the listening address is invalid
     * @throws RuntimeException if listening on this address fails (already in use etc.)
     *
     * @return resource|\UVTcp|\UVUdp|\UVPipe
     */
    public static function secure(
        $uri = null,
        array $options = [],
        string $ssl_path = null,
        string $name = null,
        array $details = []
    ) {
        $context = \stream_context_create($options);

        if (!self::$isSecure) {
            yield self::certificate($ssl_path, $name, $details);
        }

        #Setup the SSL Options
        \stream_context_set_option($context, 'ssl', 'local_cert', self::$certificate); // Our SSL Cert in PEM format
        \stream_context_set_option($context, 'ssl', 'local_pk', self::$privatekey); // Our RSA key in PEM format
        \stream_context_set_option($context, 'ssl', 'passphrase', null); // Private key Password
        \stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        \stream_context_set_option($context, 'ssl', 'verify_peer', false);
        //\stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        \stream_context_set_option($context, 'ssl', 'capath', self::$caPath);
        \stream_context_set_option($context, 'ssl', 'SNI_enabled', true);
        \stream_context_set_option($context, 'ssl', 'disable_compression', true);

        // get crypto method from context options
        self::$method = \STREAM_CRYPTO_METHOD_SSLv23_SERVER
            | \STREAM_CRYPTO_METHOD_TLS_SERVER
            | \STREAM_CRYPTO_METHOD_TLSv1_1_SERVER
            | \STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;

        if ((float) \phpversion() >= 7.4)
            self::$method |= \STREAM_CRYPTO_METHOD_TLSv1_3_SERVER;

        #create a stream socket on IP:Port
        $socket = yield self::server($uri, $context);
        \stream_socket_enable_crypto($socket, false, self::$method);

        return $socket;
    }

    /**
     * Creates self signed certificate
     *
     * @param string $ssl_path
     * @param string $name
     * @param array $details - certificate details
     *
     * Example:
     * ``
     *  array $details = [
     *      "countryName" =>  '',
     *      "stateOrProvinceName" => '',
     *      "localityName" => '',
     *      "organizationName" => '',
     *      "organizationalUnitName" => '',
     *      "commonName" => '',
     *      "emailAddress" => ''
     *  ];
     * ``
     */
    protected static function certificate(
        string $ssl_path = null,
        string $name = null,
        array $details = []
    ) {
        if (empty($ssl_path)) {
            $ssl_path = \IS_UV ? \uv_cwd() : \getcwd();
            $ssl_path = \preg_replace('/\\\/', \DS, $ssl_path) . \DS;
        } elseif (\strpos($ssl_path, \DS, -1) === false) {
            $ssl_path = $ssl_path . \DS;
        }

        $hostname = empty($name) ? \gethostname() : $name;
        $privatekeyFile = self::$privatekey = $hostname . '.pem';
        $certificateFile = self::$certificate = $hostname . '.crt';
        $signingFile = $hostname . '.csr';
        self::$caPath = $ssl_path;
        self::$isSecure = true;

        $isSignedReady = yield \file_exist($ssl_path . $privatekeyFile);
        if (!$isSignedReady) {
            // @codeCoverageIgnoreStart
            $make = function () use ($ssl_path, $details, $signingFile, $privatekeyFile, $certificateFile, $hostname) {
                $opensslConfig = array("config" => $ssl_path . 'openssl.cnf');

                // Generate a new private (and public) key pair
                $privatekey = \openssl_pkey_new($opensslConfig);

                if (empty($details))
                    $details = ["commonName" => $hostname];

                // Generate a certificate signing request
                $csr = \openssl_csr_new($details, $privatekey, $opensslConfig);

                // Create a self-signed certificate valid for 365 days
                $sslcert = \openssl_csr_sign($csr, null, $privatekey, 365, $opensslConfig);

                // Create key file. Note no passphrase
                \openssl_pkey_export_to_file($privatekey, $ssl_path . $privatekeyFile, null, $opensslConfig);

                // Create server certificate
                \openssl_x509_export_to_file($sslcert, $ssl_path . $certificateFile, false);

                // Create a signing request file
                \openssl_csr_export_to_file($csr, $ssl_path . $signingFile);
            };
            // @codeCoverageIgnoreEnd

            yield \awaitable_process(function () use ($make) {
                return yield Kernel::addProcess($make);
            });
        }
    }

    /**
     * @param string $uri
     * @param array $parts
     *
     * @return array
     */
    protected static function checkUri(string $uri = '', array $parts = [])
    {
        if (empty($parts)) {
            $parts = \parse_url($uri);
        }

        // ensure URI contains TCP scheme, host and port
        if (
            !$parts || !isset($parts['scheme'], $parts['host'], $parts['port'])
            || !\in_array($parts['scheme'], ['file', 'tcp', 'tls', 'http', 'https', 'ssl', 'udp', 'unix'])
        ) {
            throw new \InvalidArgumentException('Invalid URI "' . $uri . '" given');
        }

        $host = \trim($parts['host'], '[]');
        $isScheme = ($parts['scheme'] == 'file') || ($parts['scheme'] == 'unix') && (bool) \filter_var($host, \FILTER_VALIDATE_DOMAIN);
        if (\ip2long($host) || (\strpos($host, ':') !== false) || @\inet_pton($host)) {
            if (false === \filter_var($host, \FILTER_VALIDATE_IP))
                throw new \InvalidArgumentException('Given URI "' . $uri . '" does not contain a valid host IP');
        } elseif (false === \filter_var($host, \FILTER_VALIDATE_DOMAIN, \FILTER_FLAG_HOSTNAME)) {
            if ($isScheme === false)
                throw new \InvalidArgumentException('Given URI "' . $uri . '" does not contain a valid host DOMAIN');
        }

        return $parts;
    }

    protected static function makeUri($uri = null)
    {
        // a single port has been given => assume localhost
        if ((string) (int) $uri === (string) $uri) {
            $hostname = \gethostname();
            $ip = yield \dns_address($hostname, false);
            // @codeCoverageIgnoreStart
            if (!\is_int(\ip2long($ip)))
                throw new \Exception('Could not attain hostname IP!');
            // @codeCoverageIgnoreEnd

            $uri = $ip . ':' . $uri;
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

        $parts = self::checkUri($uri, $parts);

        $uri = \str_replace(['https', 'http'], 'tcp', $uri);

        return [$parts, $uri];
    }
}
