<?php

use Async\Coroutine\Call;
use Async\Coroutine\SpawnInterface;
use Async\Coroutine\StreamSocket;
use Async\Coroutine\StreamSocketInterface;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoroutineInterface;
use Async\Processor\Processor;
use Async\Processor\ProcessInterface;

if (! \function_exists('coroutine_run')) {
	/**
	 * Add/schedule an `yield`-ing `function/callable/task` for execution.
	 * The passed in `function/callable/task` is wrapped within `awaitAble`
	 * - This function needs to be prefixed with `yield from`
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
	 * 
	 * @param Generator|callable $asyncFunction
	 * @param mixed $args
	 * 
	 * @return int task id
	 */
	function async(callable $asyncFunction, ...$args)
	{
		return yield Call::createTask(\awaitAble($asyncFunction, ...$args));
	}	

	/**
	 * Use to obtain the result of coroutine execution
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @see https://www.python.org/dev/peps/pep-0492/#id56
	 * 
	 * @param Generator|callable $awaitableFunction
	 * @param mixed $args
	 * 
	 * @return mixed
	 */
	function await(callable $awaitedFunction, ...$args) 
	{
		return Call::await($awaitedFunction, ...$args);
	}

	/**
	 * Wrap the callable with `yield`, this makes sure every callable is a generator function,
	 * and will switch at least once without actually executing.
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
	 * 
	 * @param Generator|callable $awaitableFunction
	 * @param mixed $args
	 * 
	 * @return mixed
	 */
	function awaitAble(callable $awaitableFunction, ...$args) 
	{
		return yield $awaitableFunction(...$args);
	}	

	/**
	 * Block/sleep for delay seconds.
	 * Suspends the calling task, allowing other tasks to run.
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#sleeping
	 * 
	 * @param float $delay
	 * @param mixed $result - If provided, it is returned to the caller when the coroutine complete
	 */
	function async_sleep(float $delay = 0.0, $result = null) 
	{
		return Call::sleepFor($delay, $result); 
	}
	
	/**
	 * Wait for the callable to complete with a timeout.
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#timeouts
	 * 
	 * @param callable $callable
	 * @param float $timeout
	 */
	function async_wait_for(callable $callable, float $timeout = 0.0) 
	{
		return Call::waitFor($callable, $timeout); 
	}	

	function async_cancel(int $tid)
	{
		return Call::cancelTask($tid); 
	}	

	function async_id()
	{
		return Call::taskId();
	}	

	function async_read_wait($stream)
	{
		return Call::readWait($stream); 
	}

	function async_write_wait($stream)
	{
		return Call::writeWait($stream);
	}	

	function secure_server(
		$uri = null, 
		array $options = [],	
		string $privatekeyFile = 'privatekey.pem', 
		string $certificateFile = 'certificate.crt', 
		string $signingFile = 'signing.csr',
		string $ssl_path = null, 
		array $details = []) : StreamSocketInterface
	{
		return StreamSocket::secureServer($uri, $options, $privatekeyFile, $certificateFile, $signingFile, $ssl_path, $details);
	}

	function create_server($uri = null, array $options = []): StreamSocketInterface
	{
		return StreamSocket::createServer($uri, $options);
	}	

	function create_client($uri = null, array $options = [], bool $isRequest = false)
	{
		return StreamSocket::createClient($uri, $options, $isRequest);
	}

	function client_read(StreamSocketInterface $socket, int $size = 20240) 
	{
		return $socket->response($size);
	}

	function client_write(StreamSocketInterface $socket, string $response = null) 
	{
		return \writeSocket($socket, $response);
	}

	function close_client(StreamSocketInterface $socket)
	{
		return \closeSocket($socket);
	}	

	function accept_socket(StreamSocketInterface $socket)
	{
		return $socket->accept();
	}	

	function read_socket(StreamSocketInterface $socket, int $size = 8192)
	{
		return $socket->read($size);
	}	

	function write_socket(StreamSocketInterface $socket, string $response = null)
	{
		return $socket->write($response);
	}	

	function closeSocket(StreamSocketInterface $socket)
	{
		return $socket->close();
	}	

	function remote_ip(StreamSocketInterface $socket)
	{
		return $socket->address();
	}

	function read_input(int $size = 1024)
	{
		 return StreamSocket::input($size);
	}
	
	function coroutine_instance()
	{
		return \coroutine_create();
	}

	function coroutine_clear()
	{
		global $__coroutine__;
		$__coroutine__ = null;
	}

	function coroutine_create(\Generator $coroutine = null)
	{
		global $__coroutine__;

		if (! $__coroutine__ instanceof CoroutineInterface)
			$__coroutine__ = new Coroutine();

		if (! empty($coroutine))
			return $__coroutine__->createTask($coroutine);

		return $__coroutine__;
	}
	
	function coroutine_run()
	{
		$coroutine = \coroutine_instance();

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
		$coroutine = \coroutine_instance();

		if ($coroutine instanceof CoroutineInterface)			
			return $coroutine->addProcess($callable, $timeout);
	}

    /**
     * Get/create process worker pool of an spawn instance.
	 * 
     * @return ProcessInterface
     */
    function spawn_instance(): SpawnInterface
    {
		$coroutine = \coroutine_instance();

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
    function spawn_add($somethingToRun, int $timeout = 300): ProcessInterface
    {
		return Processor::create($somethingToRun, $timeout);
	}

    /**
     * Execute process pool, wait for results. Will do other stuff come back later.
	 * 
     * @return array
     */
    function spawn_wait(): ?array
    {
		$pool = \spawn_instance();
		
		if ($pool instanceof SpawnInterface)	
			return $pool->wait();
		
		return array();
    }
}
