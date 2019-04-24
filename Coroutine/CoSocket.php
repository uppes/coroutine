<?php

namespace Async\Coroutine;

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoSocketInterface;

class CoSocket implements CoSocketInterface
{
    protected $socket;
    protected static $caPath = \DIRECTORY_SEPARATOR;
    protected static $isSecure = false;
    protected static $privatekey = 'privatekey.pem';
    protected static $context = [];
    protected static $method = null;

    public function __construct($socket) 
	{
        $this->socket = $socket;
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
    public static function create($uri = null, $context = []) 
	{
        // a single port has been given => assume localhost
        if ((string)(int)$uri === (string)$uri) {
            $uri = '127.0.0.1:' . $uri;
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
		
        // ensure URI contains TCP scheme, host and port
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['port']) 
            || $parts['scheme'] != 'tcp')
         {
            throw new \InvalidArgumentException('Invalid URI "' . $uri . '" given');
		}
		
        if (false === \filter_var(\trim($parts['host'], '[]'), \FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Given URI "' . $uri . '" does not contain a valid host IP');
        }
        
        if (empty($context))
            $context = \stream_context_create($context);

        #create a stream socket on IP:Port
        $socket = @\stream_socket_server(
            $uri, 
            $errNo, 
            $errStr,
            \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN, 
            \stream_context_create(['socket' => $context])
        );

        if (!$socket)
            throw new \RuntimeException('Failed to listen on "' . $uri . '": ' . $errStr, $errNo);

		\stream_set_blocking($socket, false);

		return (self::$isSecure) ? $socket : new self($socket);
    }

    public static function secure($uri = null, 
        array $options = []) 
	{
        $context = \stream_context_create($options);

        if (! self::$isSecure) {
            CoSocket::createCert();
        }

        #Setup the SSL Options
        \stream_context_set_option($context, 'ssl', 'local_cert', '.'.self::$caPath.self::$privatekey);		// Our SSL Cert in PEM format
        \stream_context_set_option($context, 'ssl', 'passphrase', null);	// Private key Password
        \stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        \stream_context_set_option($context, 'ssl', 'verify_peer', false);
        \stream_context_set_option($context, 'ssl', 'ciphers', 'DHE-RSA-AES256-SHA:LONG-CIPHER');
        
        // get crypto method from context options
        $method = \STREAM_CRYPTO_METHOD_TLS_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_0_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
        self::$method = $method;

        #create a stream socket on IP:Port
        self::$context = $context;
        $socket = CoSocket::create($uri, $context);

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
        array $details = ["commonName" => "localhost"]
    ) 
    {
        if (empty($ssl_path)) {
            $ssl_path = \getcwd();
            $ssl_path = \preg_replace('/\\\/', \DIRECTORY_SEPARATOR, $ssl_path). \DIRECTORY_SEPARATOR;
        } else
            $ssl_path = $ssl_path. \DIRECTORY_SEPARATOR;

        self::$privatekey = $privatekeyFile;
        self::$caPath = $ssl_path;
        self::$isSecure = true;
        
        $opensslConfig = array("config" => $ssl_path.'openssl.cnf');
        
        // Generate a new private (and public) key pair
        $privatekey = \openssl_pkey_new($opensslConfig);
            
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
        
    public function address()
    {
        if (!\is_resource($this->socket)) {
            return null;
        }

        $address = \stream_socket_get_name($this->socket, false);

        // check if this is an IPv6 address which includes multiple colons but no square brackets
        $pos = \strrpos($address, ':');

        if ($pos !== false && \strpos($address, ':') < $pos && \substr($address, 0, 1) !== '[') {
            $port = \substr($address, $pos + 1);
            $address = '[' . \substr($address, 0, $pos) . ']:' . $port;
        }

        return 'tcp://' . $address;
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
        \stream_set_blocking($socket, false);

        \restore_error_handler();

        if (false === $result) {
            if (\feof($socket) || $error === null) {
                // EOF or failed without error => connection closed during handshake
                throw new \UnexpectedValueException(
                    'Connection lost during TLS handshake');
            } else {
                // handshake failed with error message
                throw new \UnexpectedValueException(
                    'Unable to complete TLS handshake: ' . $error
                );
            }
        }

        return $socket;
    }

    public function accept() 
	{
        yield Call::waitForRead($this->socket);
        if (self::$isSecure) {
            \stream_set_blocking($this->socket, true);
            $socket  = \stream_socket_accept($this->socket, 0);
            \stream_set_blocking($this->socket, false);
            yield Coroutine::value(new CoSocket($this->acceptSecure($socket)));
        } else
            yield Coroutine::value(new CoSocket(\stream_socket_accept($this->socket, 0)));
    }
	
    public function read(int $size) 
	{
        yield Call::waitForRead($this->socket);
        yield Coroutine::value(\fread($this->socket, $size));
    }

    public function write(string $string) 
	{
        yield Call::waitForWrite($this->socket);
        \fwrite($this->socket, $string);
    }

    public function close() 
	{
        @\fclose($this->socket);
    }
}
