<?php

namespace Async\Coroutine;

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;
use Async\Coroutine\StreamSocketInterface;

class StreamSocket implements StreamSocketInterface
{
    protected $socket;
    protected $secure;
    protected $client;
    protected $buffer = null;
    protected static $isClient = false;
    protected static $remote = null;
    protected static $caPath = \DIRECTORY_SEPARATOR;
    protected static $isSecure = false;
    protected static $privatekey = 'privatekey.pem';
    protected static $certificate = 'certificate.crt';
    protected static $method = null;

    public function __construct($socket, bool $isClient = false) 
	{
        $this->socket = $socket;
        if ($isClient) {
            self::$isClient = true;
            $this->client = $socket;
        }
    }

    private static function checkUri(array $parts = [], string $uri = '') 
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

    public static function createClient(string $uri = null, array $context = [], bool $skipInterface = false) 
	{
        // assume default scheme if none has been given
        if (\strpos($uri, '://') === false) {
            $uri = 'tcp://' . $uri;
        }

        #Connect to Server
        $socket = @\stream_socket_client(
            $uri, 
            $errNo,
            $errStr, 
            30, 
            \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_ASYNC_CONNECT, 
            stream_context_create($context)
        );

        if (!$socket)
            throw new \RuntimeException('Failed to connect to "' . $uri . '": ' . $errStr, $errNo);
    
	    \stream_set_blocking ($socket, true);
	    \stream_socket_enable_crypto ($socket, true, \STREAM_CRYPTO_METHOD_TLS_CLIENT);
        \stream_set_blocking ($socket, false);
                
		return ($skipInterface === false) ? new self($socket, true) : $socket;
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

        #create a stream socket on IP:Port
        $socket = @\stream_socket_server(
            $uri, 
            $errNo, 
            $errStr,
            \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN, 
            $context
        );

        if (!$socket)
            throw new \RuntimeException('Failed to listen on "' . $uri . '": ' . $errStr, $errNo);
        
        print "Listening to {$uri} for connections\n";

		\stream_set_blocking($socket, false);

		return (self::$isSecure) ? $socket : new self($socket);
    }

    public static function secureServer(
        $uri = null, 
        array $options = [],
        string $privatekeyFile = 'privatekey.pem', 
        string $certificateFile = 'certificate.crt', 
        string $signingFile = 'signing.csr',
        string $ssl_path = null, 
        array $details = [])
	{
        $context = \stream_context_create($options);

        if (! self::$isSecure) {
            StreamSocket::createCert($privatekeyFile, $certificateFile, $signingFile, $ssl_path, $details);
        }

        #Setup the SSL Options 
        \stream_context_set_option($context, 'ssl', 'local_cert', self::$certificate); // Our SSL Cert in PEM format
        \stream_context_set_option($context, 'ssl', 'local_pk', self::$privatekey); // Our RSA key in PEM format
        \stream_context_set_option($context, 'ssl', 'passphrase', null); // Private key Password
        \stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        \stream_context_set_option($context, 'ssl', 'verify_peer', false);
        \stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        \stream_context_set_option($context, 'ssl', 'capath', '.'.self::$caPath);
        \stream_context_set_option($context, 'ssl', 'SNI_enabled', true);
        \stream_context_set_option($context, 'ssl', 'disable_compression', true);

        // get crypto method from context options
        self::$method = \STREAM_CRYPTO_METHOD_SSLv23_SERVER | \STREAM_CRYPTO_METHOD_TLS_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;

        #create a stream socket on IP:Port
        $socket = StreamSocket::createServer($uri, $context);
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
     *  array $details = [
     *      "countryName" =>  '',
     *      "stateOrProvinceName" => '',
     *      "localityName" => '',
     *      "organizationName" => '',
     *      "organizationalUnitName" => '',
     *      "commonName" => '',
     *      "emailAddress" => ''
     *  ];
     */
    public static function createCert(
        string $privatekeyFile = 'privatekey.pem', 
        string $certificateFile = 'certificate.crt', 
        string $signingFile = 'signing.csr',
        string $ssl_path = null, 
        array $details = []
    ) 
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
        
    public function address()
    {
        return self::$remote;
    }
    
    public function acceptSecure($socket) 
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

    public function handshake() 
	{
        \stream_set_blocking($this->socket, true);
        $this->secure  = $this->acceptConnection($this->socket);
        \stream_set_blocking($this->socket, false);
        yield Coroutine::value(new StreamSocket($this->acceptSecure($this->secure)));
    }

    public function accept() 
	{
        yield Call::readWait($this->socket);
        if (self::$isSecure) {
            return $this->handshake();
        } else
            yield Coroutine::value(new StreamSocket($this->acceptConnection($this->socket)));
    }

    public function acceptConnection($socket) 
	{
        $newSocket = \stream_socket_accept($socket, 0, self::$remote);

        if (false === $newSocket) {
            throw new \RuntimeException('Error accepting new connection');
        }

        return $newSocket;
    }
    
    public function getBuffer()
    {
        return $this->buffer;
    }

    public function response(int $size = 20240) 
	{
        if (self::$isClient) {
            $this->buffer = '';
            while (!\feof($this->client)) {
                $this->buffer .= \fread($this->client, $size);
                yield;
            }
        }
    }

    public static function input(int $size = 256) 
	{
		//Check on STDIN stream
		\stream_set_blocking(\STDIN, false);
		yield Call::readWait(\STDIN);
		yield Coroutine::value(\trim(\stream_get_line(\STDIN, $size, \PHP_EOL)));
    }

    public function read(int $size = 8192) 
	{
        yield Call::readWait($this->socket);
        yield Coroutine::value(\fread($this->socket, $size));
        \stream_set_blocking($this->socket, false);
    }

    public function write(string $string) 
	{
        yield Call::writeWait($this->socket);
        yield Coroutine::value(\fwrite($this->socket, $string));
    }

    public function close() 
	{
        @\fclose($this->socket);
    }
}
