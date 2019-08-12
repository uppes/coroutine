<?php

declare(strict_types = 1);

use Async\Coroutine\Kernel;
use Async\Coroutine\Channel;
use Async\Coroutine\ServerSocket;
use Async\Coroutine\ServerSocketInterface;
use Async\Coroutine\ClientSocket;
use Async\Coroutine\ClientSocketInterface;
use Async\Coroutine\FileStream;
use Async\Coroutine\FileStreamInterface;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoroutineInterface;
use Async\Processor\Processor;
use Async\Coroutine\ParallelInterface;
use Async\Processor\ProcessInterface;

if (! \function_exists('coroutine_run')) {
	\define('MILLISECOND', 0.001);
	\define('EOL', \PHP_EOL);
	\define('DS', \DIRECTORY_SEPARATOR);


	/**
	 * Makes an resolvable function from label name that's callable with `await`
	 * The passed in `function/callable/task` is wrapped to be `awaitAble`
     *
	 * This will create closure function in global namespace with supplied name as variable
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
     * If used in conjunction with `async()` then `$awaitableFunction` is an variable that will be placed
     * in global namespace, your local calling function will need use `global` keyword with same variable
     * in local namespace also.
     *
	 * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
	 *
	 * - This function needs to be prefixed with `yield`
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
	 * Controls how the `gather()` function operates.
	 *
	 * @param int $race - If set, initiate a competitive race between multiple tasks.
	 * - When amount of tasks as completed, the `gather` will return with task results.
	 * - When `0` (default), will wait for all to complete.
	 * @param bool $exception - If `false`, the first raised exception is immediately propagated to the task that awaits on gather().
	 * Other awaitables in the aws sequence won’t be cancelled and will continue to run.
	 * - If `true` (default), exceptions are treated the same as successful results, and aggregated in the result list.
	 * @throws \LengthException - If the number of tasks less than the desired $race.
	 *
	 */
	function gather_options(int $race = 0, bool $exception = true)
	{
		Kernel::gatherOptions($race, $exception);
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
	 * - This function needs to be prefixed with `yield`
	 *
	 * @param int|array $taskId
	 * @return array
	 */
	function gather(...$taskId)
	{
		return Kernel::gather(...$taskId);
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
	function await_process($command, $timeout = 300)
	{
		return Kernel::awaitProcess($command, $timeout);
	}

	/**
	 * Wrap the callable with `yield`, this makes sure every callable is a generator function,
	 * and will switch at least once without actually executing.
 	 * Then function is used by `await` not really called directly.
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
	function sleep_for(float $delay = 0.0, $result = null)
	{
		return Kernel::sleepFor($delay, $result);
	}

	/**
	 * Creates an communications Channel between coroutines
	 * Similar to Google Go language - basic, still needs additional functions
	 * - This function needs to be prefixed with `yield`
	 *
	 * @return Channel $channel
	 */
	function make()
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
	function sender(Channel $channel, $message = null, int $taskId = 0)
	{
		return Kernel::sender($channel, $message, $taskId);
	}

	/**
	 * Set task as Channel receiver
	 * - This function needs to be prefixed with `yield`
	 *
	 * @param Channel $channel
	 */
	function receiver(Channel $channel)
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
	function receive(Channel $channel)
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
	function go($goFunction, ...$args)
	{
		return Kernel::await($goFunction, ...$args);
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
	function wait_for($callable, float $timeout = 0.0)
	{
		return Kernel::waitFor($callable, $timeout);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function cancel_task(int $tid)
	{
		return Kernel::cancelTask($tid);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function task_id()
	{
		return Kernel::taskId();
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function read_wait($stream)
	{
		return Kernel::readWait($stream);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function write_wait($stream)
	{
		return Kernel::writeWait($stream);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function input_wait(int $size = 256, bool $error = false)
	{
		return FileStream::input($size, $error);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function create_client($uri = null, array $options = [])
	{
		return ClientSocket::create($uri, $options);
	}

	function client_meta(ClientSocketInterface $instance): ?array
	{
		return $instance->meta();
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function client_read(ClientSocketInterface $instance, int $size = -1)
	{
		return $instance->read($size);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function client_write(ClientSocketInterface $instance, string $response = null)
	{
		return $instance->write($response);
	}

	function client_close(ClientSocketInterface $instance)
	{
		return $instance->close();
	}

	function client_valid(ClientSocketInterface $instance): bool
	{
		return $instance->valid();
	}

	function client_handle(ClientSocketInterface $instance): ?resource
	{
		return $instance->handle();
	}

	function client_instance(): ClientSocketInterface
	{
		return ClientSocket::instance();
	}

	function secure_server(
		$uri = null,
		array $options = [],
		string $privatekeyFile = 'privatekey.pem',
		string $certificateFile = 'certificate.crt',
		string $signingFile = 'signing.csr',
		string $ssl_path = null,
		array $details = []) : ServerSocketInterface
	{
		return ServerSocket::secureServer($uri, $options, $privatekeyFile, $certificateFile, $signingFile, $ssl_path, $details);
	}

	function create_server($uri = null, array $options = []): ServerSocketInterface
	{
		return ServerSocket::createServer($uri, $options);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function server_accept(ServerSocketInterface $instance)
	{
		return $instance->accept();
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function server_read(ServerSocketInterface $instance, int $size = -1)
	{
		return $instance->read($size);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function server_write(ServerSocketInterface $instance, string $response = null)
	{
		return $instance->write($response);
	}

	function server_response(ServerSocketInterface $instance, $body = null, $status = 200 ): string
	{
		return $instance->response($body, $status);
	}

	function server_error(ServerSocketInterface $instance, $status = 404 ):string
	{
		return $instance->error($status);
	}

	function server_close(ServerSocketInterface $instance)
	{
		return $instance->close();
	}

	/**
	 * Open file or url.
	 * - This function needs to be prefixed with `yield`
	 *
	 * @param resource|null $instance - create instance if null
	 * @param string $filename|url
	 * @param string $mode|port
	 * @param resource|array $options
	 * @return object
	 */
	function file_open(string $filename = null, $mode = 'r', $options = [])
	{
		return Kernel::fileOpen($filename, $mode, $options);
    }

    function is_type($var, string $comparing = null)
    {
        $checks = [
            'is_callable' => 'callable',
            'is_string' => 'string',
            'is_integer' => 'int',
            'is_float' => 'float',
            'is_null' => 'null',
            'is_bool' => 'bool',
            'is_array' => 'array',
            'is_object' => 'object',
            'is_resource' => 'resource',
        ];

        foreach ($checks as $func => $val) {
            if ($func($var)) {
                return (empty($comparing)) ? $val : ($comparing == $val);
            }
        }

        return 'unknown';
    }

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function get_file(string $filename)
	{
		$object = yield \file_open($filename);
		if (\file_valid($object)) {
			$contents = yield \file_Contents($object);
			\file_close($object);
			return $contents;
		}

		return false;
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function put_file(string $filename, $contents = null)
	{
		$object = yield \file_open($filename, 'w');
		if (\file_valid($object)) {
			$written = yield \file_create($object, $contents);
			\file_close($object);
			return $written;
		}

		return false;
	}

	function file_close(FileStreamInterface $instance)
	{
		return $instance->fileClose();
	}

	/**
	 * Check if valid open file handle, which file exists and readable.
	 *
	 * @param resource $instance
	 * @return bool
	 */
	function file_valid(FileStreamInterface $instance): bool
	{
		return $instance->fileValid();
	}

	/**
	 * Get file contents from open file handle, reading by size chucks, with timeout
	 * - This function needs to be prefixed with `yield`
	 *
	 * @param resource $instance
	 * @param int $size
	 * @param float $timeout_seconds
	 * @return mixed
	 */
	function file_contents(FileStreamInterface $instance, int $size = -1, float $timeout_seconds = 0.5, $stream = null)
	{
		return $instance->fileContents($size, $timeout_seconds, $stream);
    }

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function file_create(FileStreamInterface $instance, $contents, $stream = null)
	{
		return $instance->fileCreate($contents, $stream);
	}

	/**
	 * - This function needs to be prefixed with `yield`
	 */
	function file_lines(FileStreamInterface $instance)
	{
		return $instance->fileLines();
	}

	function file_meta(FileStreamInterface $instance)
	{
		return $instance->fileMeta();
	}

	function file_status(FileStreamInterface $instance, $meta = null)
	{
		return $instance->fileStatus($meta);
	}

	function file_handle(FileStreamInterface $instance)
	{
		return $instance->fileHandle();
	}

	function file_instance(): FileStreamInterface
	{
		return FileStream::fileInstance();
	}

	function remote_ip(ServerSocketInterface $instance)
	{
		return $instance->address();
	}

	function coroutine_instance()
	{
		return \coroutine_create();
	}

	function coroutine_clear()
	{
		global $__coroutine__;
		$__coroutine__ = null;
        unset($GLOBALS['__coroutine__']);
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
    }
}
