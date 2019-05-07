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

	function async_remove(int $tid)
	{
		return Call::removeTask($tid); 
	}	

	function async_id()
	{
		return Call::taskId();
	}	

	function asyncReadStream($stream)
	{
		return Call::readWait($stream); 
	}

	function asyncWriteStream($stream)
	{
		return Call::writeWait($stream);
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
		return \coroutineCreate();
	}

	function coroutineCreate(\Generator $coroutine = null)
	{
		global $__coroutine__;

		if (! $__coroutine__ instanceof CoroutineInterface)
			$__coroutine__ = new Coroutine();

		if (! empty($coroutine))
			return $__coroutine__->createTask($coroutine);

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
    
    function read_input()
    {
        //Check on STDIN stream
        $read = [STDIN];
        yield Call::readWait($read);			
        yield Coroutine::value(\trim(\stream_get_line(STDIN, 1024, PHP_EOL)));
    }
}
