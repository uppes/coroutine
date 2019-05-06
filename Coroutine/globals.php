<?php

use Async\Coroutine\Call;
use Async\Coroutine\CoSocket;
use Async\Coroutine\Coroutine;
use Async\Coroutine\SpawnInterface;
use Async\Coroutine\CoSocketInterface;
use Async\Coroutine\CoroutineInterface;
use Async\Coroutine\Spawn;
use Async\Processor\Processor;
use Async\Processor\ProcessInterface;

if (! \function_exists('coroutineRun')) {
	function async(callable $asyncFunction, ...$args) 
	{
		return yield Call::addTask(\awaitAble($asyncFunction, ...$args));
	}	

	function await(callable $awaitedFunction, ...$args) 
	{
		$value = yield from \awaitAble($awaitedFunction, ...$args);
		yield Coroutine::value($value);
	}

	function awaitAble(callable $awaitableFunction, ...$args) 
	{
		return yield $awaitableFunction(...$args);
	}	

	function asyncRemove(int $tid)
	{
		return Call::removeTask($tid); 
	}	

	function asyncId()
	{
		return Call::taskId();
	}	

	function asyncReadStream($stream)
	{
		return Call::waitForRead($stream); 
	}

	function asyncWriteStream($stream)
	{
		return Call::waitForWrite($stream);
	}	

	function secureServer(
		$uri = null, 
		array $options = [],	
		string $privatekeyFile = 'privatekey.pem', 
		string $certificateFile = 'certificate.crt', 
		string $signingFile = 'signing.csr',
		string $ssl_path = null, 
		array $details = []) : CoSocketInterface
	{
		return CoSocket::secureServer($uri, $options, $privatekeyFile, $certificateFile, $signingFile, $ssl_path, $details);
	}

	function createServer($uri = null, array $options = []): CoSocketInterface
	{
		return CoSocket::createServer($uri, $options);
	}	

	function createClient($uri = null, array $options = [], bool $isRequest = false)
	{
		return CoSocket::createClient($uri, $options, $isRequest);
	}

	function clientRead(CoSocketInterface $socket, int $size = 20240) 
	{
		return $socket->response($size);
	}

	function clientWrite(CoSocketInterface $socket, string $response = null) 
	{
		return \writeSocket($socket, $response);
	}

	function closeClient(CoSocketInterface $socket)
	{
		return \closeSocket($socket);
	}	

	function acceptSocket(CoSocketInterface $socket)
	{
		return $socket->accept();
	}	

	function readSocket(CoSocketInterface $socket, int $size = 8192)
	{
		return $socket->read($size);
	}	

	function writeSocket(CoSocketInterface $socket, string $response = null)
	{
		return $socket->write($response);
	}	

	function closeSocket(CoSocketInterface $socket)
	{
		return $socket->close();
	}	

	function remoteAddress(CoSocketInterface $socket)
	{
		return $socket->address();
	}

	function coroutineInstance()
	{
		return \coroutineAdd();
	}

	function coroutineAdd(\Generator $coroutine = null)
	{
		global $__coroutine__;

		if (! $__coroutine__ instanceof CoroutineInterface)
			$__coroutine__ = new Coroutine();

		if (! empty($coroutine))
			return $__coroutine__->addTask($coroutine);

		return $__coroutine__;
	}
	
	function coroutineRun()
	{
		$coroutine = \coroutineInstance();

		if ($coroutine instanceof CoroutineInterface) {			
			$coroutine->run();
			return true;
		}

		return false;
	}

    /**
     * Add something/callable to `coroutine` process pool
	 * 
     * @param callable $callable
     * @param int $timeout 
     *
     * @return ProcessInterface
     */
	function spawn($callable, int $timeout = 300): ProcessInterface
    {
		$coroutine = \coroutineInstance();

		if ($coroutine instanceof CoroutineInterface)			
			return $coroutine->addProcess($callable, $timeout);
	}

    /**
     * Get/create process worker pool of an spawn instance.
	 * 
     * @return ProcessInterface
     */
    function spawnInstance(): SpawnInterface
    {
		$coroutine = \coroutineInstance();

		if ($coroutine instanceof CoroutineInterface)			
			return $coroutine->spawnInstance();
	}

    /**
     * Add something/callable to spawn instance process pool.
	 * 
     * @param callable $somethingToRun
     * @param int $timeout 
     *
     * @return ProcessInterface
     */
    function spawnAdd($somethingToRun, int $timeout = 300): ProcessInterface
    {
		return Processor::create($somethingToRun, $timeout);
	}

    /**
     * Execute process pool, wait for results. Will do other stuff come back later.
	 * 
     * @return array
     */
    function spawnWait(): ?array
    {
		$pool = \spawnInstance();
		
		if ($pool instanceof SpawnInterface)	
			return $pool->wait();
		
		return array();
    }
}
