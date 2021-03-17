<?php

declare(strict_types=1);

namespace Async\Coroutine;

/**
 * A simple generic class for handling/constructing **client/server**
 * messages, following the https://tools.ietf.org/html/rfc2616.html specs.
 *
 * This class works similar to `PSR-7`, not fully representing, usage is
 * for quick operations.
 */
class NetworkAssistant
{
    const AGENT = 'PHP Client';
    const SERVER = 'PHP Server';

    /**
     * Valid HTTP status codes and reasons.
     *
     * Verified 2020-05-22
     *
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @var array
     */
    protected static $statusCodes = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * The current response status
     *
     * @var int
     */
    protected $status = 200;

    /**
     * The current response body
     *
     * @var string
     */
    protected $body = '';

    /**
     * The unchanged data from server
     *
     * @var string
     */
    protected $raw = '';

    /**
     * The current headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The protocol
     *
     * @var string
     */
    protected $protocol = '';

    /**
     * The protocol version
     *
     * @var float
     */
    protected $version = 1.1;

    /**
     * The requested status code
     *
     * @var string
     */
    protected $code = '';

    /**
     * The requested status message
     *
     * @var string
     */
    protected $message = '';

    /**
     * The requested method
     *
     * @var string
     */
    protected $method = '';

    /**
     * The requested path
     *
     * @var string
     */
    protected $path = '';

    /**
     * The requested uri
     *
     * @var string
     */
    protected $uri = '';

    /**
     * The request params
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $hostname = '';

    /**
     * @var string
     */
    protected $action = '';

    /**
     * Response headers to send
     *
     * @var array
     */
    protected $header = [];

    /**
     * Set how this class will be used.
     *
     * @param string $action either `response` or `request`
     * @param string $hostname for `Host:` header request, this will be ignored on `path/url` setting
     * @param string $protocol version for `HTTP/` header
     */
    public function __construct(string $action = '', string $hostname = '', float $protocol = 1.1)
    {
        $this->action = $action;
        $this->hostname = $hostname;
        $this->version = $protocol;
    }

    /**
     * Return a request header `content`.
     *
     * @param string $key header to retrieve or `all`
     * @param string $default
     *
     * @return string|array
     */
    public function getHeader(string $key = 'all', $default = '')
    {
        if (\strtolower($key) == 'all') {
            $default = $this->headers;
        } elseif ($this->hasHeader($key)) {
            $default = $this->headers[\strtolower($key)];
        }

        return $default;
    }

    /**
     * Return a request header content `variable` value.
     *
     * @param string $key header to check for
     * @param string $var variable to find
     * @param mixed $default value to return if not found
     *
     * @return mixed
     */
    public function getVariable(string $key, string $var, $default = '')
    {
        if ($this->hasVariable($key, $var)) {
            $line = $this->getHeader($key);
            $sections = \strpos($line, '; ') !== false ? \explode('; ', $line) : [$line];
            foreach ($sections as $parts) {
                $variable = \explode('=', $parts);
                if ($variable[0] === $var) {
                    return isset($variable[1]) ? $variable[1] : $default;
                }
            }
        }

        return $default;
    }

    /**
     * Return a request parameter `value`.
     *
     * @param string $key parameter to retrieve or `all`
     * @param mixed $default value to return if not found
     *
     * @return string|array
     */
    public function getParameter(string $key = 'all', $default = '')
    {
        if (\strtolower($key) == 'all') {
            $default = $this->parameters;
        } elseif ($this->hasParameter($key)) {
            $default = $this->parameters[$key];
        }

        return $default;
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function hasHeader(string $key): bool
    {
        return isset($this->headers[\strtolower($key)]);
    }

    /**
     * @param string $key
     * @param string $var
     *
     * @return boolean
     */
    public function hasVariable(string $key, string $var): bool
    {
        return @\strpos($this->getHeader($key), $var . '=') !== false;
    }

    /**
     * @param string $key
     * @param string $flag
     *
     * @return boolean
     */
    public function hasFlag(string $key, string $flag): bool
    {
        return @\strpos($this->getHeader($key), $flag . ';') !== false
            || @\strpos($this->getHeader($key), $flag . ',') !== false
            || @\strpos($this->getHeader($key), $flag . \CRLF) !== false;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasParameter(string $key)
    {
        return isset($this->parameters[$key]);
    }

    /**
     * Return the response status code.
     *
     * @return int
     */
    public function getCode()
    {
        return (int) $this->code;
    }

    /**
     * Return the response status message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Return the protocol.
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Return the request method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return the request path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the request uri.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * The server received body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Parse received server headers, and store.
     *
     * @param string $headers
     *
     * @return void
     */
    public function parse($headers)
    {
        $this->headers = [];
        $this->parameters = [];
        $this->path = '';
        $this->method = '';
        $this->code = 200;
        $this->message = '';
        $this->protocol = '';
        $this->raw = $headers;

        $headers = \explode("\n\n", $this->raw, 2);
        $this->body = isset($headers[1]) ? $headers[1] : '';
        $lines = isset($headers[0]) ? \explode("\n", $headers[0]) : [];
        if (\count($lines) > 1) {
            foreach ($lines as $line) {
                // clean the line
                $line = \trim($line);
                $found = false;
                if (\strpos($line, ': ') !== false) {
                    $found = true;
                    list($key, $value) = \explode(': ', $line);
                } elseif (\strpos($line, ':') !== false) {
                    $found = true;
                    list($key, $value) = \explode(':', $line);
                } elseif (\strpos($line, 'HTTP/') !== false && ($this->action == 'request')) {
                    list($this->method, $this->path, $this->protocol) = \explode(' ', $line, 3);
                } elseif (\strpos($line, 'HTTP/') !== false && ($this->action == 'response')) {
                    list($this->protocol, $this->code, $this->message) = \explode(' ', $line, 3);
                }

                if ($found) {
                    $this->headers[\strtolower($key)] = $value;
                }
            }

            if ($this->action == 'request') {
                // split path and parameters string
                @list($this->path, $params) = \explode('?', $this->path);

                // parse the parameters
                if (!empty($params)) {
                    \parse_str($params, $this->parameters);
                }
            }
        }
    }

    /**
     * Construct a new response string.
     *
     * @param string $body defaults to `Not Found`, if empty and `$status`
     * @param int $status defaults to `404`, if empty and `$body`, otherwise `200`
     * @param string|null $type
     * @param array $extra additional headers - associative array
     *
     * @return string
     */
    public function response(
        ?string $body = null,
        ?int $status = null,
        ?string $type = 'text/html',
        array ...$extra
    ) {
        if (!\is_null($status)) {
            $this->status = $status;
        }

        if (empty($body) && empty($status)) {
            $this->status = $status = 404;
        }

        $this->body = empty($body)
            ? "<h1>" . self::SERVER
            . ": " . $status . " - "
            . static::$statusCodes[$status]
            . "</h1>"
            : $body;

        // set initial headers
        $this->header('Date', \gmdate('D, d M Y H:i:s T'));
        $this->header('Content-Type', (empty($type) ? 'text/html' : $type) . '; charset=utf-8');
        $this->header('Content-Length', \strlen($this->body));
        $this->header('Server', self::SERVER);

        if (isset($extra[0])) {
            foreach ($extra[0] as $key => $value) {
                if (!empty($key) && \is_string($key))
                    $this->header($key, $value);
            }
        }

        // Create a string out of the response data
        $lines = [];

        // response status
        $lines[] = (empty($this->protocol) ? 'HTTP/' . $this->version : $this->protocol)
            . ' '
            . $this->status . ' '
            . static::$statusCodes[$this->status];

        // add the headers
        foreach ($this->header as $key => $value) {
            $lines[] = $key . ': ' . $value;
        }

        // Build a response header string based on the current line data.
        $headerString = \implode("\r\n", $lines) . "\r\n\r\n";

        return (string) $headerString . (string) $this->body;
    }

    /**
     * Construct a new request string.
     *
     * @param string $method
     * @param string $path
     * @param string|null $type
     * @param string|null $data
     * @param array $extra additional headers - associative array
     *
     * @return string
     */
    public function request(
        string $method = 'GET',
        string $path = '/',
        ?string $type = 'text/html',
        $data = null,
        array ...$extra
    ) {
        $this->uri = (\strpos($path, '://') === false) ? $this->hostname . $path : $path;
        $url_array = \parse_url($this->uri);
        $hostname = isset($url_array['host']) ? $url_array['host'] : $this->hostname;
        $path = '/';
        if (isset($url_array['path'])) {
            $path = $url_array['path'];
            $path .= isset($url_array['query']) ? '?' . $url_array['query'] : '';
            $path .= isset($url_array['fragment']) ? '#' . $url_array['fragment'] : '';
        }

        $headers = \trim(\strtoupper($method)) . " $path HTTP/" . (string) $this->version . \CRLF;
        $headers .= "Host: " . \trim($hostname) . \CRLF;
        $headers .= "Accept: */*" . \CRLF;
        if (!empty($data)) {
            $headers .= "Content-Type: " . (empty($type) ? 'text/html' : $type) . "; charset=utf-8" . \CRLF;
            $headers .= "Content-Length: " . \strlen($data) . \CRLF;
        }

        if (isset($extra[0])) {
            foreach ($extra[0] as $key => $value) {
                if (!empty($key) && \is_string($key))
                    $headers .= \ucwords($key, '-') . ': ' . $value . \CRLF;
            }
        }

        $headers .= "User-Agent: " . self::AGENT . \CRLF;
        $headers .= "Connection: close" . \CRLF . \CRLF;
        if (!empty($data))
            $headers .= $data;

        return $headers;
    }

    /**
     * Add or overwrite an response header parameter.
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    protected function header($key, $value)
    {
        $this->header[\ucwords($key, '-')] = $value;
    }
}
