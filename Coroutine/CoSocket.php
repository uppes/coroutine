<?php

namespace Async\Coroutine;

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoSocketInterface;

class CoSocket implements CoSocketInterface
{
    protected $socket;
    protected static $isSecure = false;
    protected static $privatekey = 'privkey.pem';
    protected static $context = ["commonName" => "localhost"];

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
    public static function create($uri = null, array $context = []) 
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
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['port']) || $parts['scheme'] !== 'tcp') {
            throw new \InvalidArgumentException('Invalid URI "' . $uri . '" given');
		}
		
        if (false === \filter_var(\trim($parts['host'], '[]'), \FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Given URI "' . $uri . '" does not contain a valid host IP');
        }
        
        if (! is_resource($context))
            $context = \stream_context_create(['socket' => $context]);

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

		\stream_set_blocking($socket, 0);

		return new self($socket);
    }

    public static function secure($uri = null, array $context = []) 
	{
        $context = \stream_context_create($context);

        if (! self::$isSecure) {
            CoSocket::createCert();
        }

        #Setup the SSL Options
        \stream_context_set_option($context, 'ssl', 'local_cert', self::$privatekey);		// Our SSL Cert in PEM format
        \stream_context_set_option($context, 'ssl', 'passphrase', null);	// Private key Password
        \stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        \stream_context_set_option($context, 'ssl', 'verify_peer', false);

        #create a stream socket on IP:Port
        $socket = CoSocket::create($uri, $context);

        // get crypto method from context options
        $method = \STREAM_CRYPTO_METHOD_TLS_SERVER;
        if (isset($context['ssl']['crypto_method'])) {
            $method = $context['ssl']['crypto_method'];
        }

        $error = null;
        \set_error_handler(function ($_, $errstr) use (&$error) {
            $error = \str_replace(array("\r", "\n"), ' ', $errstr);
            // remove useless function name from error message
            if (($pos = \strpos($error, "): ")) !== false) {
                $error = \substr($error, $pos + 3);
            }
        });

        $result = \stream_socket_enable_crypto($socket, false, $method);

        \restore_error_handler();

        if (false === $result) {
            if (\feof($socket) || $error === null) {
                // EOF or failed without error => connection closed during handshake
                throw new \UnexpectedValueException(
                    'Connection lost during TLS handshake',
                    \defined('SOCKET_ECONNRESET') ? \SOCKET_ECONNRESET : 0
                );
            } else {
                // handshake failed with error message
                throw new \UnexpectedValueException(
                    'Unable to complete TLS handshake: ' . $error
                );
            }
        }

		\stream_set_blocking($socket, 0);

		return $socket;
    }

    public static function createCert(
        string $pem_file = 'privkey.pem', 
        array $pem_dn = ["commonName" => "localhost"]) 
    {
        self::$privatekey = $pem_file;
        self::$context = $pem_dn;
        self::$isSecure = true;

        #create ssl cert for this scripts life.
        //== Determine path
        $ssl_path = \getcwd();
        $ssl_path = \preg_replace('/\\\/', \DIRECTORY_SEPARATOR, $ssl_path);
        
        $Configs = array("config" => $ssl_path. \DIRECTORY_SEPARATOR .'openssl.cnf');
        
        #Create private key
        $privkey = \openssl_pkey_new($Configs);
            
        #Create and sign CSR
        $cert    = \openssl_csr_new($pem_dn, $privkey, $Configs);
        $cert    = \openssl_csr_sign($cert, null, $privkey, 365, $Configs);
            
        \openssl_pkey_export_to_file($privkey, $pem_file, null, $Configs);
        \openssl_x509_export_to_file($cert, "server.crt",  false );
        
        #Generate PEM file
        //$pem = array();
        //openssl_x509_export($cert, $pem[0]);
        //openssl_pkey_export($privkey, $pem[1], null);
        //$pem = implode($pem);
        #Save PEM file
        //file_put_contents($pem_file, $pem);
        //chmod($pem_file, 0600);
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
    
    public function accept() 
	{
        yield Call::waitForRead($this->socket);
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
