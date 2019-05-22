<?php

use Async\Coroutine\Kernel;
use Async\Coroutine\Channel;
use Async\Coroutine\ParallelInterface;
use Async\Coroutine\StreamSocket;
use Async\Coroutine\StreamSocketInterface;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoroutineInterface;
use Async\Processor\Processor;
use Async\Processor\ProcessInterface;

if (! \function_exists('coroutine_run')) {	
	define('MILLISECOND', 0.001);
	define('EOL', \PHP_EOL);


	/**
	 * Makes an resolvable function from label name that's callable with `await`
	 * The passed in `function/callable/task` is wrapped to be `awaitAble`
	 * 
	 * @param string $labelFunction
	 * @param Generator|callable $asyncFunction
	 */
	function async(string $labelFunction = '__f', callable $asyncFunction)
	{
		Kernel::async($labelFunction, $asyncFunction);
	}	

	/**
	 * Add/schedule an `yield`-ing `function/callable/task` for execution.
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
	 * 
	 * @param Generator|callable $awaitableFunction
	 * @param mixed $args
	 * 
	 * @return int $task id
	 */
	function await($awaitableFunction, ...$args) 
	{
		return Kernel::await($awaitableFunction, ...$args);
	}

	/**
	 * Run awaitable objects in the taskId sequence concurrently.
	 * If any awaitable in taskId is a coroutine, it is automatically scheduled as a Task.
	 * 
	 * If all awaitables are completed successfully, the result is an aggregate list of returned values. 
	 * The order of result values corresponds to the order of awaitables in taskId.
	 * 
	 * The first raised exception is immediately propagated to the task that awaits on gather(). 
	 * Other awaitables in the sequence won’t be cancelled and will continue to run.
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.gather
	 * 
	 * @param int|array $taskId
	 * @return array
	 */
	function gather(...$taskId)
	{
		return Kernel::gather(...$taskId) ;
	}

	/**
	 * Add and wait for result of an blocking io subprocess, will run in parallel.
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
	 * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
	 * 
	 * @param callable|shell $command
	 * @param int $timeout
	 * 
	 * @return mixed
	 */
	function await_blocking($command, $timeout = 300)
	{
		return Kernel::awaitProcess($command, $timeout);
	}

	/**
	 * Wrap the callable with `yield`, this makes sure every callable is a generator function,
	 * and will switch at least once without actually executing.
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
	 * 
	 * @param TaskInterface $task
	 * @param Generator|callable $awaitableFunction
	 * @param mixed $args
	 * 
	 * @return mixed
	 */
	function awaitAble(callable $awaitableFunction, ...$args) 
	{
		return yield yield $awaitableFunction(...$args);
	}	

	/**
	 * Block/sleep for delay seconds.
	 * Suspends the calling task, allowing other tasks to run.
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#sleeping
	 * 
	 * @param float $delay
	 * @param mixed $result - If provided, it is returned to the caller when the coroutine complete
	 */
	function async_sleep(float $delay = 0.0, $result = null) 
	{
		return Kernel::sleepFor($delay, $result); 
	}

	/**
	 * Creates an communications Channel between coroutines
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @return Channel $channel
	 */
	function async_channel() 
	{
		return Kernel::make();
	}

	/**
	 * Creates an Channel similar to Google Go language
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @return Channel $channel
	 */
	function go_make() 
	{
		return Kernel::make();
	}

	/**
	 * Send message to an Channel
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @param Channel $channel
   	 * @param mixed $message
	 * @param int $taskId
	 */
	function go_sender(Channel $channel, $message = null, int $taskId = 0)
	{
		return Kernel::sender($channel, $message, $taskId);
	}

	/**
	 * Set task as Channel receiver
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @param Channel $channel
	 */
	function go_receiver(Channel $channel)
	{
		return Kernel::receiver($channel); 
	}

	/**
	 * Receive Channel message
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @param Channel $channel
	 * @return mixed
	 */
	function go_receive(Channel $channel)
	{
		return Kernel::receive($channel);
	}
	
	/**
	 * A goroutine is a function that is capable of running concurrently with other functions. 
	 * To create a goroutine we use the keyword `go` followed by a function invocation
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @see https://www.golang-book.com/books/intro/10#section1
	 * 
	 * @param callable $goFunction
	 * @param mixed $args
	 * @return int task id
	 */
	function go(callable $goFunction, ...$args) 
	{
		return Kernel::await($goFunction, ...$args);
	}

	/**
	 * Block/sleep for delay seconds.
	 * Suspends the calling task, allowing other tasks to run.
	 * - This function needs to be prefixed with `yield`
	 *  
	 * @param float $delay
	 * @param mixed $result - If provided, it is returned to the caller when the coroutine complete
	 */
	function go_sleep(float $delay = 0.0, $result = null) 
	{
		return Kernel::sleepFor($delay, $result); 
	}
	
	/**
	 * Wait for the callable to complete with a timeout.
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#timeouts
	 * 
	 * @param callable $callable
	 * @param float $timeout
	 */
	function async_wait_for(callable $callable, float $timeout = 0.0) 
	{
		return Kernel::waitFor($callable, $timeout); 
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function async_cancel(int $tid)
	{
		return Kernel::cancelTask($tid); 
	}	

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function async_id()
	{
		return Kernel::taskId();
	}	

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function async_read_wait($stream)
	{
		return Kernel::readWait($stream); 
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function async_write_wait($stream)
	{
		return Kernel::writeWait($stream);
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

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function client_read(StreamSocketInterface $socket, int $size = -1) 
	{
		return $socket->response($size);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function client_write(StreamSocketInterface $socket, string $response = null) 
	{
		return \write_socket($socket, $response);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function close_client(StreamSocketInterface $socket)
	{
		return \close_socket($socket);
	}	

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function accept_socket(StreamSocketInterface $socket)
	{
		return $socket->accept();
	}	

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function read_socket(StreamSocketInterface $socket, int $size = -1)
	{
		return $socket->read($size);
	}	

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function write_socket(StreamSocketInterface $socket, string $response = null)
	{
		return $socket->write($response);
	}	

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function close_socket(StreamSocketInterface $socket)
	{
		return $socket->close();
	}

	/**
	 * Open file or url.
	 * 
	 * @param resource $socket
	 * @param string $filename|url
	 * @param string $mode|port
	 * @return object
	 */
	function open_file(StreamSocketInterface $socket = null, string $filenameUrl = null, $modePort = 'r'): StreamSocketInterface
	{
		if (empty($socket))
			$socket = new StreamSocket(null);

		$socket->openFile($filenameUrl, $modePort);

		return $socket;
	}

	function file_get(StreamSocketInterface $socket, string $getPath = '/', $format = 'text/html')
	{
		return $socket->get($getPath, $format);
	}

	function close_file(StreamSocketInterface $socket)
	{
		return $socket->closeFile();
	}

	/**
	 * Check if valid open file handle, which file exists and readable.
	 * 
	 * @param resource $socket
	 * @return bool
	 */
	function file_valid(StreamSocketInterface $socket): bool
	{
		return $socket->fileValid();
	}	

	/**
	 * Get file contents from open file handle, reading by size chucks, with timeout
	 * - This function needs to be prefixed with `yield`
	 * 
	 * @param resource $socket
	 * @param int $size
	 * @param float $timeout_seconds
	 * @return mixed
	 */
	function file_contents(StreamSocketInterface $socket, int $size = 256, float $timeout_seconds = 0.5)
	{
		return $socket->fileContents($size, $timeout_seconds);
	}

	function file_meta(StreamSocketInterface $socket, $handle = null)
	{
		return $socket->getMeta($handle);
	}

	function file_status(StreamSocketInterface $socket)
	{
		return $socket->status();
	}

	function remote_ip(StreamSocketInterface $socket)
	{
		return $socket->address();
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function read_input(int $size = 256)
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
			$__coroutine__->createTask($coroutine);

		return $__coroutine__;
	}
	
	/**
	 * This function runs the passed coroutine, taking care of managing the scheduler and 
	 * finalizing asynchronous generators. It should be used as a main entry point for programs, and 
	 * should ideally only be called once.
	 * 
	 * @see https://docs.python.org/3.8/library/asyncio-task.html#asyncio.run
	 * 
	 * @param Generator $coroutine
	 */
	function coroutine_run(\Generator $coroutine = null)
	{
		$coroutine = \coroutine_create($coroutine);

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
	function parallel($callable, int $timeout = 300): ProcessInterface
    {
		$coroutine = \coroutine_instance();

		if ($coroutine instanceof CoroutineInterface)			
			return $coroutine->createSubProcess($callable, $timeout);
	}

    /**
     * Get/create process worker pool of an parallel instance.
	 * 
     * @return ProcessInterface
     */
    function parallel_instance(): ParallelInterface
    {
		$coroutine = \coroutine_instance();

		if ($coroutine instanceof CoroutineInterface)			
			return $coroutine->parallelInstance();
	}

    /**
     * Add something/callable to parallel instance process pool.
	 * 
     * @param callable $somethingToRun
     * @param int $timeout 
     *
     * @return ProcessInterface
     */
    function parallel_add($somethingToRun, int $timeout = 300): ProcessInterface
    {
		return Processor::create($somethingToRun, $timeout);
	}

    /**
     * Execute process pool, wait for results. Will do other stuff come back later.
	 * 
     * @return array
     */
    function parallel_wait(): ?array
    {
		$pool = \parallel_instance();
		
		if ($pool instanceof ParallelInterface)	
			return $pool->wait();
		
		return array();
    }
}
