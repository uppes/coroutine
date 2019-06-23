<?php

declare(strict_types = 1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\SecureStreamSocket;
use Async\Coroutine\StreamSocketInterface;

class ServerSocket implements ServerSocketInterface
{
    protected $socket;
    protected $resource;
    protected $secure;
    protected $instance;
    protected $meta = [];
    protected $metaData = [];
    protected $host = '';
    
    public static $caPath = \DIRECTORY_SEPARATOR;
    public static $privatekey = 'privatekey.pem';
    public static $certificate = 'certificate.crt';
    protected static $method = null;

    protected static $isSecure = false;
    protected static $remote = null;

	/**
	 * An array of the available HTTP response codes
	 *
	 * @var array
	 */
	protected static $statusCodes = [
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',
	
		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
	
		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',
	
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
	
		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
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
	 * The current response headers
	 *
	 * @var array
	 */
    protected $headers = [];
    
    public function __construct($server) 
	{
        $this->instance = $this;
        $this->socket = $server;
    }

    protected static function checkUri(array $parts = [], string $uri = '') 
    {
        if (empty($parts))
            $parts = \parse_url($uri);

        // ensure URI contains TCP scheme, host and port
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['port']) 
            || !\in_array($parts['scheme'], ['tcp', 'tls', 'http', 'https', 'ssl', 'udp', 'unix'])
        ) {
            throw new \InvalidArgumentException('Invalid URI "' . $uri . '" given');
		}
		
        if (false === \filter_var(\trim($parts['host'], '[]'), \FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Given URI "' . $uri . '" does not contain a valid host IP');
        }
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
     * @param string|int    $uri
     * @param array         $context
     * @throws InvalidArgumentException if the listening address is invalid
     * @throws RuntimeException if listening on this address fails (already in use etc.)
     */
    public static function createServer($uri = null, $context = []) 
	{
        $hostname = \gethostname();
        $ip = \gethostbyname($hostname);

        // a single port has been given => assume localhost
        if ((string)(int)$uri === (string)$uri) {
            $uri = $ip.':' . $uri;
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
			
        self::checkUri($parts, $uri);
        
        if (empty($context))
            $context = \stream_context_create($context);

        #create a stream server on IP:Port
        $server = @\stream_socket_server(
            $uri, 
            $errNo, 
            $errStr,
            \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN, 
            $context
        );

        if (!$server)
            throw new \RuntimeException('Failed to listen on "' . $uri . '": ' . $errStr, $errNo);
        
        print "Listening to {$uri} for connections\n";

		\stream_set_blocking($server, false);

		return (self::$isSecure) ? $server : new self($server);
    }

    public function address()
    {
        return self::$remote;
    }    

    public function handshake()
	{
        \stream_set_blocking($this->socket, true);
        $this->secure  = $this->acceptConnection($this->socket);
        \stream_set_blocking($this->socket, false);
        return Coroutine::value(new SecureStreamSocket(SecureStreamSocket::acceptSecure($this->secure)));
    }

    public function accept()
	{
        yield Kernel::readWait($this->socket);
        if (self::$isSecure) {
            yield $this->handshake();
        } else
            yield Coroutine::value(new StreamSocket($this->acceptConnection($this->socket)));
    }

    protected function acceptConnection($socket)
	{
        $newSocket = \stream_socket_accept($socket, 0, self::$remote);

        if (false === $newSocket) {
            throw new \RuntimeException('Error accepting new connection');
        }

        return $newSocket;
    }

	/**
	 * Construct a new response
	 *
	 * @param string 		$body
	 * @param int 			$status
	 * @return string
	 */
	public function response($body, $status = null )
	{
		if ( !is_null( $status ) )
		{
			$this->status = $status;
		}
		
		$this->body = $body;
		
		// set initial headers
        $this->header( 'Date', gmdate( 'D, d M Y H:i:s T' ) );
        $this->header( 'Content-Type', 'text/html; charset=utf-8' );
        $this->header( 'Server', 'Symplely Server' );

        // Create a string out of the response data
        return (string) $this->buildHeaderString() . (string) $this->body;
    }

	/**
	 * Returns a simple response based on a status code
	 *
	 * @param int $status
	 * @return string
	 */
	public function error($status)
	{
		return $this->response("<h1>Symplely Server: ".$status." - ".static::$statusCodes[$status]."</h1>", $status );
    }
    	
	/**
	 * Add or overwrite an header parameter header 
	 *
	 * @param string 			$key
	 * @param string 			$value
	 * @return void
	 */
	public function header( $key, $value )
	{
		$this->headers[\ucfirst($key)] = $value;
	}
	
	/**
	 * Build a header string based on the current object
	 *
	 * @return string
	 */
	public function buildHeaderString()
	{
		$lines = [];
		
		// response status 
		$lines[] = 'HTTP/1.1 '.$this->status.' '.static::$statusCodes[$this->status];
		
		// add the headers
		foreach( $this->headers as $key => $value )
		{
			$lines[] = $key.': '.$value;
		}
		
		return \implode( " \r\n", $lines )."\r\n\r\n";
	}

    public function read(int $size = -1) 
	{
        if (!\is_resource($this->socket))
            return false;

        yield Kernel::readWait($this->socket);
        yield Coroutine::value(\stream_get_contents($this->socket, $size));
        \stream_set_blocking($this->socket, false);
    }

    public function write(string $string) 
	{
        if (!\is_resource($this->socket))
            return false;

        yield Kernel::writeWait($this->socket);
        yield Coroutine::value(\fwrite($this->socket, $string));
    }

    public function close() 
	{
        $resource = $this->socket;
        $this->socket = null;
        $this->secure = null;
        $this->body = null;
            
        if (\is_resource($resource))
            @\fclose($resource);
    }

    public static function acceptSecure($socket) 
	{
        $error = null;
        \set_error_handler(function ($_, $errstr) use (&$error) {
            $error = \str_replace(array("\r", "\n"), ' ', $errstr);
            // remove useless function name from error message
            if (($pos = \strpos($error, "): ")) !== false) {
                $error = \substr($error, $pos + 3);
            }
        });

        \stream_set_blocking($socket, true);
        $result = @\stream_socket_enable_crypto($socket, true, self::$method);

        \restore_error_handler();

        if (false === $result) {
            if (\feof($socket) || $error === null) {
                // EOF or failed without error => connection closed during handshake
                print 'Connection lost during TLS handshake with: '. self::$remote . "\n";
            } else {
                // handshake failed with error message
                print 'Unable to complete TLS handshake: ' . $error . "\n";
            }
        }
 
        return $socket;
    }

    public static function secureServer($uri = null, array $options = [], string $privatekeyFile = 'privatekey.pem', string $certificateFile = 'certificate.crt',      string $signingFile = 'signing.csr', string $ssl_path = null, array $details = [])
	{
        $context = \stream_context_create($options);

        if (! self::$isSecure) {
            self::createCert($privatekeyFile, $certificateFile, $signingFile, $ssl_path, $details);
        }

        #Setup the SSL Options 
        \stream_context_set_option($context, 'ssl', 'local_cert', self::$certificate); // Our SSL Cert in PEM format
        \stream_context_set_option($context, 'ssl', 'local_pk', self::$privatekey); // Our RSA key in PEM format
        \stream_context_set_option($context, 'ssl', 'passphrase', null); // Private key Password
        \stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        \stream_context_set_option($context, 'ssl', 'verify_peer', false);
        //\stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        \stream_context_set_option($context, 'ssl', 'capath', '.'.self::$caPath);
        //\stream_context_set_option($context, 'ssl', 'SNI_enabled', true);
        \stream_context_set_option($context, 'ssl', 'disable_compression', true);

        // get crypto method from context options
        self::$method = \STREAM_CRYPTO_METHOD_SSLv23_SERVER | \STREAM_CRYPTO_METHOD_TLS_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;

        #create a stream socket on IP:Port
        $socket = self::createServer($uri, $context);
        \stream_socket_enable_crypto($socket, false, self::$method);

		return new self($socket);
    }

    /**
     * Creates self signed certificate
     * 
     * @param string $privatekeyFile
     * @param string $certificateFile
     * @param string $signingFile
     * @param string $ssl_path
     * @param array $details - certificate details 
     * 
     * Example: 
     * ```
     *  array $details = [
     *      "countryName" =>  '',
     *      "stateOrProvinceName" => '',
     *      "localityName" => '',
     *      "organizationName" => '',
     *      "organizationalUnitName" => '',
     *      "commonName" => '',
     *      "emailAddress" => ''
     *  ];
     * ```
     */
    public static function createCert(string $privatekeyFile = 'privatekey.pem', string $certificateFile = 'certificate.crt', string $signingFile = 'signing.csr', string $ssl_path = null, array $details = []) 
    {
        if (empty($ssl_path)) {
            $ssl_path = \getcwd();
            $ssl_path = \preg_replace('/\\\/', \DIRECTORY_SEPARATOR, $ssl_path). \DIRECTORY_SEPARATOR;
        } elseif (\strpos($ssl_path, \DIRECTORY_SEPARATOR, -1) === false)
            $ssl_path = $ssl_path. \DIRECTORY_SEPARATOR;

        self::$privatekey = $privatekeyFile;
        self::$certificate = $certificateFile;
        self::$caPath = $ssl_path;
        self::$isSecure = true;
        
        if (! \file_exists('.'.$ssl_path.$privatekeyFile)) {
            $opensslConfig = array("config" => $ssl_path.'openssl.cnf');

            // Generate a new private (and public) key pair
            $privatekey = \openssl_pkey_new($opensslConfig);

            if (empty($details))
                $details = ["commonName" => \gethostname()];

            // Generate a certificate signing request
            $csr = \openssl_csr_new($details, $privatekey, $opensslConfig);
        
            // Create a self-signed certificate valid for 365 days
            $sslcert = \openssl_csr_sign($csr, null, $privatekey, 365, $opensslConfig);
        
            // Create key file. Note no passphrase
            \openssl_pkey_export_to_file($privatekey, $ssl_path.$privatekeyFile, null, $opensslConfig);
        
            // Create server certificate 
            \openssl_x509_export_to_file($sslcert, $ssl_path.$certificateFile, false);
            
            // Create a signing request file 
            \openssl_csr_export_to_file($csr, $ssl_path.$signingFile);
        }
    }        
}
