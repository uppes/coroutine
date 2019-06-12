<?php

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\StreamSocket;
use Async\Coroutine\StreamSocketInterface;

class SecureStreamSocket extends StreamSocket
{
    protected static $caPath = \DIRECTORY_SEPARATOR;
    protected static $privatekey = 'privatekey.pem';
    protected static $certificate = 'certificate.crt';
    protected static $method = null;
    
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
