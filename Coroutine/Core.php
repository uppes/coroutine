<?php

declare(strict_types=1);

use Async\Spawn\Channeled;
use Async\Coroutine\Defer;
use Async\Coroutine\Kernel;
use Async\Coroutine\Channel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoroutineInterface;
use Async\Coroutine\Exceptions\Panic;
use Async\Coroutine\FileSystem;
use Async\Coroutine\Network;
use Async\Coroutine\NetworkAssistant;

if (!\function_exists('coroutine_run')) {
    /**
     * Multiply with to convert to seconds from a millisecond number.
     * Use with `sleep_for()`.
     *
     * @var float
     */
    \define('MS', 0.001);
    \define('EOL', \PHP_EOL);
    \define('CRLF', "\r\n");

    /**
     * Makes an resolvable function from label name that's callable with `away`
     * The passed in `function/callable/task` is wrapped to be `awaitAble`
     *
     * This will create closure function in global namespace with supplied name as variable.
     *
     * @param string $labelFunction
     * @param Generator|callable $asyncFunction
     */
    function async(string $labelFunction, callable $asyncFunction)
    {
        Kernel::async($labelFunction, $asyncFunction);
    }

    /**
     * Wrap the value with `yield`, when placed within code block, it insure that
     * any *function/method* will be `awaitable` and the actual return value is properly picked up.
     *
     * use as: `return \value($value);`
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @internal
     */
    function value($value)
    {
        yield;
        return yield $value;
    }

    /**
     * Creates an object instance of the value which will signal, and insure the actual return value is properly picked up.
     *
     * use as: `return \result($value);`
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @internal
     */
    function result($value)
    {
        yield Coroutine::value($value);
    }

    /**
     * Add/schedule an `yield`-ing `function/callable/task` for background execution.
     * Will immediately return an `int`, and continue to the next instruction.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
     *
     * - This function needs to be prefixed with `yield`
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args - if `generator`, $args can hold `customState`, and `customData`
     *
     * @return int $task id
     */
    function away($awaitableFunction, ...$args)
    {
        return Kernel::away($awaitableFunction, ...$args);
    }

    /**
     * Run awaitable objects in the tasks set concurrently and block until the condition specified by race.
     *
     * Controls how the `gather()` function operates.
     * `gather_wait` will behave like **Promise** functions `All`, `Some`, `Any` in JavaScript.
     *
     * @param array<int|\Generator> $tasks
     * @param int $race - If set, initiate a competitive race between multiple tasks.
     * - When amount of tasks as completed, the `gather` will return with task results.
     * - When `0` (default), will wait for all to complete.
     * @param bool $exception - If `true` (default), the first raised exception is immediately
     *  propagated to the task that awaits on gather().
     * Other awaitables in the aws sequence won't be cancelled and will continue to run.
     * - If `false`, exceptions are treated the same as successful results, and aggregated in the result list.
     * @param bool $clear - If `true` (default), close/cancel remaining results
     * @throws \LengthException - If the number of tasks less than the desired $race count.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#waiting-primitives
     *
     * @return array associative `$taskId` => `$result`
     */
    function gather_wait(array $tasks, int $race = 0, bool $exception = true, bool $clear = true)
    {
        return Kernel::gatherWait($tasks, $race, $exception, $clear);
    }

    /**
     * Run awaitable objects in the taskId sequence concurrently.
     * If any awaitable in taskId is a coroutine, it is automatically scheduled as a Task.
     *
     * If all awaitables are completed successfully, the result is an aggregate list of returned values.
     * The order of result values corresponds to the order of awaitables in taskId.
     *
     * The first raised exception is immediately propagated to the task that awaits on gather().
     * Other awaitables in the sequence won't be cancelled and will continue to run.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.gather
     *
     * - This function needs to be prefixed with `yield`
     *
     * @param int|array $taskId
     * @return array associative `$taskId` => `$result`
     */
    function gather(...$taskId)
    {
        return Kernel::gather(...$taskId);
    }

    /**
     * Add/execute a blocking `I/O` subprocess task that runs in parallel.
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return int
     */
    function spawn_task(
        $command,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null
    ) {
        return Kernel::spawnTask($command, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
    }

    /**
     * Add a signal handler for the signal, that's continuously monitored.
     * This function will return `int` immediately, use with `spawn_signal()`.
     * - The `$handler` function will be executed, if subprocess is terminated with the `signal`.
     * - Expect the `$handler` to receive `(int $signal)`.
     * - This function needs to be prefixed with yield
     *
     * @param int $signal
     * @param callable $handler
     *
     * @return int
     */
    function signal_task(int $signal, callable $handler)
    {
        return Kernel::signalTask($signal, $handler);
    }

    /**
     * Add/execute a blocking `I/O` subprocess task that runs in parallel.
     * Will execute the `$signalTask` task id, if subprocess is terminated with the `$signal`.
     *
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with yield
     *
     * @see https://docs.python.org/3/library/signal.html#module-signal
     *
     * @param callable|shell $command
     * @param int $signal
     * @param int|null $signalTask
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     *
     * @return int
     */
    function spawn_signal(
        $command,
        int $signal = 0,
        $signalTask = null,
        $timeout = 0,
        bool $display = false
    ) {
        return Kernel::spawnTask($command, $timeout, $display, null, null, $signal, $signalTask, 'signaling');
    }

    /**
     * Stop/kill a `child/subprocess` with `signal`, and also `cancel` the task.
     * - This function needs to be prefixed with `yield`
     *
     * @param int $tid The task id of the subprocess task.
     * @param int $signal `Termination/kill` signal constant.
     *
     * @return bool
     */
    function spawn_kill(int $tid, int $signal = \SIGKILL)
    {
        return Kernel::spawnKill($tid, $signal);
    }

    /**
     * Add a progress handler for the subprocess, that's continuously monitored.
     * This function will return `int` immediately, use with `spawn_progress()`.
     * - The `$handler` function will be executed every time the subprocess produces output.accordion
     * - Expect the `$handler` to receive `(string $type, $data)`, where `$type` is either `out` or `err`.
     * - This function needs to be prefixed with `yield`
     *
     * @param callable $handler
     *
     * @return int
     */
    function progress_task(callable $handler)
    {
        return Kernel::progressTask($handler);
    }

    /**
     * Add/execute a blocking `I/O` subprocess task that runs in parallel, but the subprocess can be controlled.
     * The passed in `task id` can be use as a IPC handler for real time interaction.
     *
     * The `$channelTask` will receive **output type** either(`out` or `err`),
     * and **the data/output** in real-time.
     *
     * Use: __Channel__ ->`send()` to write to the standard input of the process.
     *
     * This function will return `int` immediately, use `gather()` to get the result.
     * - This function needs to be prefixed with yield
     *
     * @param mixed $command
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying `process` standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     *
     * @return int
     */
    function spawn_progress(
        $command,
        $channel = null,
        $channelTask = null,
        $timeout = 0,
        bool $display = false
    ) {
        return Kernel::spawnTask($command, $timeout, $display, $channel, $channelTask, 0, null);
    }

    /**
     * Add a file change event handler for the path being watched, that's continuously monitored.
     * This function will return `int` immediately, use with `monitor()`, `monitor_file()`, `monitor_dir()`.
     * - The `$handler` function will be executed every time theres activity with the path being watched.
     * - Expect the `$handler` to receive `(?string $filename, int $events, int $status)`.
     * - This function needs to be prefixed with `yield`
     *
     * @param callable $handler
     *
     * @return int
     */
    function monitor_task(callable $handler)
    {
        return Kernel::monitorTask($handler);
    }

    /**
     * Monitor/watch the specified path for changes,
     * switching to `monitor_task()` by id to handle any changes.
     * - The `monitor_task` will receive `(?string $filename, int $events, int $status)`.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param integer $monitorTask
     *
     * @return bool
     */
    function monitor(string $path, int $monitorTask)
    {
        return FileSystem::monitor($path, $monitorTask);
    }

    /**
     * Monitor/watch the specified file for changes,
     * switching to `monitor_task()` by id to handle any changes.
     * - The `monitor_task` will receive `(?string $filename, int $events, int $status)`.
     * - This function needs to be prefixed with `yield`
     *
     * `Note:` The `file` will be created if does not already exists.
     *
     * @param string $file
     * @param integer $monitorTask
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    function monitor_file(string $file, int $monitorTask)
    {
        $file = \slash_switch($file);
        $check = yield \file_exist($file);
        if (!$check)
            yield \file_touch($file);

        return yield \monitor($file, $monitorTask);
    }

    /**
     * Monitor/watch the specified directory for changes,
     * switching to `monitor_task()` by id to handle any changes.
     * - The `monitor_task` will receive `(?string $filename, int $events, int $status)`.
     * - This function needs to be prefixed with `yield`
     *
     * `Note:` The `directory` will be created `recursively` if does not already exists.
     *
     * @param string $directory
     * @param integer $monitorTask
     *
     * @return bool
     */
    function monitor_dir(string $directory, int $monitorTask)
    {
        $directory = \slash_switch($directory);
        yield \spawn_system('mkdir', $directory, 0777, true);

        return yield \monitor($directory, $monitorTask);
    }

    function slash_switch($path)
    {
        if (\IS_WINDOWS && (\strpos('/', $path) !== false))
            $path = \str_replace('/', \DS, $path);
        elseif (\IS_LINUX && (\strpos('\\', $path) !== false))
            $path = \str_replace('\\', \DS, $path);

        return $path;
    }

    /**
     * Add and wait for result of an blocking `I/O` subprocess that runs in parallel.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return mixed
     */
    function spawn_await(
        $callable,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null
    ) {
        return \awaitable_process(function () use (
            $callable,
            $timeout,
            $display,
            $channel,
            $channelTask,
            $signal,
            $signalTask
        ) {
            return Kernel::addProcess($callable, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
        });
    }

    /**
     * Executes a blocking system call asynchronously in a **child/subprocess**.
     * By `proc_open`, or `uv_spawn` if **libuv** is loaded.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $command Any `PHP` builtin system operation command.
     * @param mixed ...$parameters
     *
     * @return  mixed
     * @throws Panic if not a callable.
     */
    function spawn_system(string $command, ...$parameters)
    {
        if (!\is_callable($command)) {
            \panic('Not a valid PHP callable command!');
        }

        // @codeCoverageIgnoreStart
        $system = function () use ($command, $parameters) {
            return @$command(...$parameters);
        };
        // @codeCoverageIgnoreEnd

        return \awaitable_process(function () use ($system) {
            return Kernel::addProcess($system);
        });
    }

    /**
     * Recursively delete files/folders asynchronously in a **child/subprocess**.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $directory
     *
     * @return bool
     */
    function file_delete($dir)
    {
        $dir = \slash_switch($dir);

        // @codeCoverageIgnoreStart
        $system = function ($dirFile) use ($dir, &$system) {
            // Need to check for string type. All child/subprocess automatically
            // have a Channel instance passed in on process execution.
            $dir = \is_string($dirFile) ? $dirFile : $dir;
            if (\is_dir($dir)) {
                $files = @\glob($dir . '*', \GLOB_MARK);
                foreach ($files as $file) {
                    $system($file);
                }

                return @\rmdir($dir);
            } elseif (\is_file($dir)) {
                return @\unlink($dir);
            }
        };
        // @codeCoverageIgnoreEnd

        yield \awaitable_process(function () use ($system) {
            return yield Kernel::addProcess($system);
        });

        $bool = yield \file_exist($dir);

        return ($bool === false);
    }

    /**
     * Sets access and modification time of file.
     * - This function needs to be prefixed with `yield`
     *
     * @param mixed $path
     * @param mixed|null $time
     * @param mixed|null $atime
     *
     * @return bool
     */
    function file_touch($path, $time = null, $atime = null)
    {
        return FileSystem::touch($path, $time, $atime);
    }

    /**
     * Renames a file or directory.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    function file_rename($from, $to)
    {
        return FileSystem::rename($from, $to);
    }

    /**
     * Deletes a file.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return bool
     */
    function file_unlink($path)
    {
        return FileSystem::unlink($path);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_link($from, $to)
    {
        return FileSystem::link($from, $to);
    }

    /**
     * Creates a symbolic link.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $from
     * @param string $to
     * @param int $flag
     *
     * @return bool
     */
    function file_symlink($from, $to, $flag = 0)
    {
        return FileSystem::symlink($from, $to, $flag);
    }

    /**
     * Read value of a symbolic link.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return string|bool
     */
    function file_readlink($path)
    {
        return FileSystem::readlink($path);
    }

    /**
     * Attempts to create the directory specified by pathname.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param integer $mode
     * @param boolean $recursive
     *
     * @return bool
     */
    function file_mkdir($path, $mode = 0777, $recursive = false)
    {
        return FileSystem::mkdir($path, $mode, $recursive);
    }

    /**
     * Removes directory.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return bool
     */
    function file_rmdir($path)
    {
        return FileSystem::rmdir($path);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_chmod($filename, $mode)
    {
        return FileSystem::chmod($filename, $mode);
    }

    /**
     * @codeCoverageIgnore
     */
    function file_chown($path, $uid, $gid)
    {
        return FileSystem::chown($path, $uid, $gid);
    }

    /**
     * List files and directories inside the specified path.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param mixed $flagSortingOrder
     *
     * @return array|bool
     */
    function file_scandir($path, $sortingOrder = 0)
    {
        return FileSystem::scandir($path, $sortingOrder);
    }

    /**
     * Gives information about a file.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param string $info
     * - Numeric    `$info` Description
     *````
     * 0    dev     device number
     * 1	ino	inode number
     * 2	mode	inode protection mode
     * 3	nlink	number of links
     * 4	uid	userid of owner
     * 5	gid	groupid of owner
     * 6	rdev	device type, if inode device
     * 7	size	size in bytes
     * 8	atime	time of last access (Unix timestamp)
     * 9	mtime	time of last modification (Unix timestamp)
     * 10	ctime	time of last inode change (Unix timestamp)
     * 11	blksize	blocksize of filesystem IO
     * 12	blocks	number of 512-byte blocks allocated
     *````
     * @return array|bool
     */
    function file_stat($path, $info = null)
    {
        return FileSystem::stat($path, $info);
    }

    /**
     * Gives information about a file symbolic link, returns same data as `stat()`.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param string $info
     * - Numeric    `$info` Description
     *````
     * 0    dev     device number
     * 1	ino	inode number
     * 2	mode	inode protection mode
     * 3	nlink	number of links
     * 4	uid	userid of owner
     * 5	gid	groupid of owner
     * 6	rdev	device type, if inode device
     * 7	size	size in bytes
     * 8	atime	time of last access (Unix timestamp)
     * 9	mtime	time of last modification (Unix timestamp)
     * 10	ctime	time of last inode change (Unix timestamp)
     * 11	blksize	blocksize of filesystem IO
     * 12	blocks	number of 512-byte blocks allocated
     *````
     * @return array|bool
     */
    function file_lstat($path, $info = null)
    {
        return FileSystem::lstat($path, $info);
    }

    /**
     * Gets information about a file using an open file pointer.
     * - This function needs to be prefixed with `yield`
     *
     * @param resource $fd
     * @param string $info
     *
     * @return array|bool
     */
    function file_fstat($fd, $info = null)
    {
        return FileSystem::fstat($fd, $info);
    }

    /**
     * Transfer data between file descriptors.
     *
     * @param resource $out_fd
     * @param resource $in_fd
     * @param int $offset
     * @param int $length
     *
     * @return int|bool
     */
    function file_sendfile($out_fd, $in_fd, int $offset = 0, int $length = 8192)
    {
        $written = yield FileSystem::sendfile($out_fd, $in_fd, $offset, $length);
        if (FileSystem::isUv()) {
            yield FileSystem::fdatasync($out_fd);
        }

        return $written;
    }

    /**
     * Open specified `$path` file with access `$flag`.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param string $flag either 'r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+':
     * - "`r`"	`read`: Open file for input operations. The file must exist.
     * - "`w`"	`write`: Create an empty file for output operations.
     * If a file with the same name already exists, its contents are discarded and the
     * file is treated as a new empty file.
     * - "`a`"	`append`: Open file for output at the end of a file.
     * Output operations always write data at the end of the file, expanding it.
     * Repositioning operations (fseek, fsetpos, rewind) are ignored.
     * The file is created if it does not exist.
     * - "`r+`" `read/update`: Open a file for update (both for input and output). The file must exist.
     * - "`w+`" `write/update`: Create an empty file and open it for update (both for input and output).
     * If a file with the same name already exists its contents are discarded and the file is
     * treated as a new empty file.
     * - "`a+`" `append/update`: Open a file for update (both for input and output) with all output
     * operations writing data at the end of the file. Repositioning operations (fseek, fsetpos,
     * rewind) affects the next input operations, but output operations move the position back
     * to the end of file. The file is created if it does not exist.
     * - "`x`" `Write only`: Creates a new file. Returns `FALSE` and an error if file already exists.
     * - "`x+`" `Read/Write`: Creates a new file. Returns `FALSE` and an error if file already exists
     * @param int $mode
     *
     * @return resource|bool
     */
    function file_open(string $path, string $flag = 'r', int $mode = \UV::S_IRWXU)
    {
        return FileSystem::open($path, $flag, $mode);
    }

    /**
     * Read file pointed to by the `resource` file descriptor.
     * - This function needs to be prefixed with `yield`
     *
     * @param resource $fd
     * @param int $offset
     * @param int $length
     *
     * @return string
     * @throws Exception
     */
    function file_read($fd, int $offset = 0, int $length = 8192)
    {
        return FileSystem::read($fd, $offset, $length);
    }

    /**
     * Write to file pointed to by the `resource` file descriptor.
     * - This function needs to be prefixed with `yield`
     *
     * @param resource $fd
     * @param string $buffer
     * @param int|bool $offset if not `UV` set to schedule immediately
     *
     * @return int|bool
     */
    function file_write($fd, string $buffer, $offset = -1)
    {
        return FileSystem::write($fd, $buffer, $offset);
    }

    /**
     * Close file pointed to by the `resource` file descriptor.
     * - This function needs to be prefixed with `yield`
     *
     * @param resource $fd
     *
     * @return bool
     */
    function file_close($fd)
    {
        return FileSystem::close($fd);
    }

    /**
     * Synchronize a file's in-core state with storage device by file descriptor.
     * - This function needs to be prefixed with `yield`
     *
     * @param resource $fd
     *
     * @return resource|bool
     */
    function file_fdatasync($fd)
    {
        return FileSystem::fdatasync($fd);
    }

    /**
     * Return file size.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return int|bool
     */
    function file_size($path)
    {
        return \file_stat($path, 'size');
    }

    /**
     * Check if file exists.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return bool
     */
    function file_exist($path)
    {
        $status = yield \file_size($path);
        return \is_int($status);
    }

    /**
     * Open url/uri.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $url
     * @param resource|array|null $context
     *
     * @return resource
     */
    function file_uri(string $url, $contexts = null)
    {
        return FileSystem::open($url, 'r', 0, $contexts);
    }

    /**
     * Reads remainder of a stream/file pointer by size into a string,
     * will stop if timeout seconds lapse.
     *
     * @param resource $fd
     * @param integer $size
     * @param float $timeout_seconds
     *
     * @return string|bool
     */
    function file_contents($fd, int $size = -1, float $timeout_seconds = 0.5)
    {
        return FileSystem::contents($fd, $size, $timeout_seconds);
    }

    /**
     * Reads entire file into a string.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $filename
     * @param int $offset
     * @param int $max
     *
     * @return string|bool
     */
    function file_get(string $filename)
    {
        $fd = yield \file_open($filename, 'r');
        if (\is_resource($fd)) {
            if (\file_meta($fd, 'wrapper_type') === 'http') {
                $max = -1;
            } else {
                if (\IS_LINUX)
                    $max = yield \file_fstat($fd, 'size');
                else
                    $max = yield \file_stat($filename, 'size');
            }

            $contents = yield \file_read($fd, 0, (empty($max) ? 8192 * 2 : $max));
            yield \file_close($fd);
            return $contents;
        }

        return false;
    }

    /**
     * Write a string to a file.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $filename
     * @param mixed $contents
     *
     * @return int|bool
     */
    function file_put(string $filename, $contents)
    {
        $fd = yield \file_open($filename, 'w');
        if (\is_resource($fd)) {
            $written = yield \file_write($fd, $contents);
            yield \file_fdatasync($fd);
            yield \file_close($fd);
            return $written;
        }

        return false;
    }

    /**
     * Reads entire file into an array.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     *
     * @return array
     */
    function file_file($path)
    {
        return \spawn_system('file', $path, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Retrieves header/meta data from streams/file pointers.
     *
     * @param resource $fd
     * @param null|string $info
     * - Can be: `timed_out`, `blocked`, `eof`, `unread_bytes`, `stream_type`, `wrapper_type`,
     * `mode`, `seekable`, `uri`, `wrapper_data`
     * - and `status` for **HTTP Status Code** from `wrapper_data`
     * - and `size` for **HTTP Content Length** from `wrapper_data`
     *
     * @return array|string|int|bool
     */
    function file_meta($fd, ?string $info = null)
    {
        return FileSystem::meta($fd, $info);
    }

    /**
     * Turn `on/off` **libuv** for file operations.
     *
     * @param bool $useUV
     * - `true` use **thread pool**.
     * - `false` use **child/subprocess**.
     */
    function file_operation(bool $useUV = false)
    {
        FileSystem::setup($useUV);
    }

    /**
     * Turn `on/off` **libuv** for network operations.
     *
     * @param bool $useUV
     * - `true` use **libuv**, currently only *Linux* works correctly, this setting have no effect on *Windows*.
     * - `false` use PHP **builtin**.
     * * @param bool $reset
     *
     * `Note:` `libuv` network operations are currently broken on the *Windows* platform.
     */
    function net_operation(bool $useUV = false, $reset = false)
    {
        Network::setup($useUV, $reset);
    }

    /**
     * Get the IP `address` corresponding to Internet host name.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $hostname
     * @param bool $useUv
     *
     * @return string|bool
     */
    function dns_address(string $hostname, bool $useUv = true)
    {
        return Network::address($hostname, $useUv);
    }

    /**
     * Get the Internet host `name` corresponding to IP address.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $ipAddress
     *
     * @return string|bool
     */
    function dns_name(string $ipAddress)
    {
        return Network::name($ipAddress);
    }

    /**
     * Get DNS Resource Records associated with hostname.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $hostname
     * @param int $options Constant type:
     * - `DNS_A` IPv4 Address Resource
     * - `DNS_CAA` Certification Authority Authorization Resource (available as of PHP 7.0.16 and 7.1.2)
     * - `DNS_MX` Mail Exchanger Resource
     * - `DNS_CNAME` Alias (Canonical Name) Resource
     * - `DNS_NS` Authoritative Name Server Resource
     * - `DNS_PTR` Pointer Resource
     * - `DNS_HINFO` Host Info Resource (See IANA Operating System Names for the meaning of these values)
     * - `DNS_SOA` Start of Authority Resource
     * - `DNS_TXT` Text Resource
     * - `DNS_ANY` Any Resource Record. On most systems this returns all resource records,
     * however it should not be counted upon for critical uses. Try DNS_ALL instead.
     * - `DNS_AAAA` IPv6 Address Resource
     * - `DNS_ALL` Iteratively query the name server for each available record type.
     *
     * @return array|bool
     */
    function dns_record(string $hostname, int $options = \DNS_A)
    {
        return Network::record($hostname, $options);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function listener_task(callable $handler)
    {
        return Network::listenerTask($handler);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_listen(
        $handle,
        int $handlerTask,
        int $backlog = 0,
        bool $isSecure = false,
        array $options = [],
        ?string $ssl_path = null,
        ?string $name = null,
        array $details = []
    ) {
        if (((string) (int) $handle === (string) $handle) || \is_string($handle)) {
            $handle = yield \net_server($handle, $isSecure, $options, $ssl_path, $name, $details);
        }

        return yield Network::listen($handle, $handlerTask, $backlog);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_client($uri = null, $optionsOrData = [])
    {
        return Network::client($uri, $optionsOrData);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_server(
        $uri = null,
        bool $isSecure = false,
        array $options = [],
        string $ssl_path = null,
        string $name = null,
        array $details = []
    ) {
        if ($isSecure)
            return Network::secure($uri, $options, $ssl_path, $name, $details);
        else
            return Network::server($uri, $options);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_stop(int $id)
    {
        return Network::stop($id);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_accept($handle)
    {
        return Network::accept($handle);
    }

    /**
     * - This function needs to be prefixed with `yield`
     *
     * @internal
     *
     * @codeCoverageIgnore
     */
    function net_connect(string $scheme, string $address, int $port, $data = null)
    {
        return Network::connect($scheme, $address, $port, $data);
    }

    /**
     * @internal
     *
     * @codeCoverageIgnore
     */
    function net_bind(string $scheme, string $address, int $port)
    {
        return Network::bind($scheme, $address, $port);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_read($handle, int $size = -1)
    {
        return Network::read($handle, $size);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_write($handle, string $response = '')
    {
        return Network::write($handle, $response);
    }

    /**
     * - This function needs to be prefixed with `yield`
     */
    function net_close($handle)
    {
        return Network::close($handle);
    }

    /**
     * Get the address of the connected handle.
     *
     * @param UVTcp|UVUdp|resource $handle
     * @return string|bool
     */
    function net_peer($handle)
    {
        return Network::peer($handle);
    }

    /**
     * Construct a new request string.
     *
     * @param object|NetworkAssistant $object
     * @param string $method
     * @param string $path
     * @param string|null $type
     * @param string|null $data
     * @param array $extra additional headers - associative array
     *
     * @return string
     */
    function net_request(
        $object,
        string $method = 'GET',
        string $path = '/',
        ?string $type = 'text/html',
        $data = null,
        array ...$extra
    ): string {
        if (
            $object instanceof NetworkAssistant
            || (\is_object($object) && \method_exists($object, 'request'))
        ) {
            return $object->request($method, $path, $type, $data, ...$extra);
        }
    }

    /**
     * Construct a new response string.
     *
     * @param object|NetworkAssistant $object
     * @param string $body defaults to `Not Found`, if empty and `$status`
     * @param int $status defaults to `404`, if empty and `$body`, otherwise `200`
     * @param string|null $type
     * @param array $extra additional headers - associative array
     *
     * @return string
     */
    function net_response(
        $object,
        ?string $body = null,
        ?int $status = null,
        ?string $type = 'text/html',
        array ...$extra
    ): string {
        if (
            $object instanceof NetworkAssistant
            || (\is_object($object) && \method_exists($object, 'response'))
        ) {
            return $object->response($body, $status, $type, ...$extra);
        }
    }

    /**
     * Add and wait for result of an blocking `I/O` subprocess that runs in parallel.
     * This function turns the calling function internal __state/type__ used by `gather()`
     * to **process/paralleled** which is handled differently.
     *
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-subprocess.html#subprocesses
     * @see https://docs.python.org/3.7/library/asyncio-dev.html#running-blocking-code
     *
     * @param callable|shell $command
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param bool $display set to show child process output
     * @param Channeled|resource|mixed|null $channel IPC communication to be pass to the underlying process standard input.
     * @param int|null $channelTask The task id to use for realtime **child/subprocess** interaction.
     * @param int $signal
     * @param int $signalTask The task to call when process is terminated with a signal.
     *
     * @return mixed
     */
    function add_process(
        $command,
        $timeout = 0,
        bool $display = false,
        $channel = null,
        $channelTask = null,
        int $signal = 0,
        $signalTask = null
    ) {
        return Kernel::addProcess($command, $timeout, $display, $channel, $channelTask, $signal, $signalTask);
    }

    /**
     * Wrap the callable with `yield`, this insure the first attempt to execute will behave
     * like a generator function, will switch at least once without actually executing, return object instead.
     * - This function is used by `away()` and others, shouldn't really be called directly.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args
     *
     * @return \Generator
     *
     * @internal
     */
    function awaitable(callable $awaitableFunction, ...$args)
    {
        return yield yield $awaitableFunction(...$args);
    }

    /**
     * Wrap the a spawn `process` with `yield`, this insure the the execution
     * and return result is handled properly.
     * - This function is used by `spawn_await()` shouldn't really be called directly.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args
     *
     * @return \Generator
     *
     * @internal
     */
    function awaitable_process(callable $awaitableFunction, ...$args)
    {
        return yield $awaitableFunction(...$args);
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
     * Creates an communications Channel between coroutines.
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
     * @param int $taskId override send to different task, not set by `receiver()`
     */
    function sender(Channel $channel, $message = null, int $taskId = 0)
    {
        $noResult = yield Kernel::sender($channel, $message, $taskId);
        yield;
        return $noResult;
    }

    /**
     * Set task as Channel receiver, and wait to receive Channel message
     * - This function needs to be prefixed with `yield`
     *
     * @param Channel $channel
     */
    function receiver(Channel $channel)
    {
        yield Kernel::receiver($channel);
        $message = yield Kernel::receive($channel);
        return $message;
    }

    /**
     * A goroutine is a function that is capable of running concurrently with other functions.
     * To create a goroutine we use the keyword `go` followed by a function invocation
     * - This function needs to be prefixed with `yield`
     *
     * @see https://www.golang-book.com/books/intro/10#section1
     *
     * @param callable $goFunction
     * @param mixed $args - if `generator`, $args can hold `customState`, and `customData`
     *
     * @return int task id
     */
    function go($goFunction, ...$args)
    {
        return Kernel::away($goFunction, ...$args);
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
     * kill/remove an task using task id.
     * Optionally pass custom cancel state and error message for third party code integration.
     *
     * - This function needs to be prefixed with `yield`
     */
    function cancel_task(int $tid, $customState = null, string $errorMessage = 'Invalid task ID!')
    {
        return Kernel::cancelTask($tid, $customState, $errorMessage);
    }

    /**
     * kill/remove the current running task.
     * Optionally pass custom `cancel` state for third party code integration.
     *
     * - This function needs to be prefixed with `yield`
     */
    function kill_task($customState = null)
    {
        $currentTask = yield Kernel::getTask();
        return yield Kernel::cancelTask($currentTask, $customState);
    }

    /**
     * Performs a clean application exit and shutdown.
     * - This function needs to be prefixed with `yield`
     *
     * Provide $skipTask incase called by an Signal Handler.
     *
     * @param int $skipTask - Defaults to the main parent task.
     * - The calling `$skipTask` task id will not get cancelled, the script execution will return to.
     * - Use `getTask()` to retrieve caller's task id.
     */
    function shutdown(int $skipTask = 1)
    {
        return Kernel::shutdown($skipTask);
    }

    /**
     * Returns the current context task ID
     *
     * - This function needs to be prefixed with `yield`
     *
     * @return int
     */
    function get_task()
    {
        return Kernel::getTask();
    }

    /**
     * Set current context Task to stateless `networked`, meaning not storing any return values or exceptions on completion.
     * The task is not moved to completed task list.
     * This function will return the current context task ID.
     *
     * - This function needs to be prefixed with `yield`
     *
     * @return int
     */
    function stateless_task()
    {
        return Kernel::statelessTask();
    }

    /**
     * Wait on read stream socket to be ready read from,
     * optionally schedule current task to execute immediately/next.
     *
     * - This function needs to be prefixed with `yield`
     */
    function read_wait($stream, bool $immediately = false)
    {
        return Kernel::readWait($stream, $immediately);
    }

    /**
     * Wait on write stream socket to be ready to be written to,
     * optionally schedule current task to execute immediately/next.
     *
     * - This function needs to be prefixed with `yield`
     */
    function write_wait($stream, bool $immediately = false)
    {
        return Kernel::writeWait($stream, $immediately);
    }

    /**
     * Wait on keyboard input.
     * Will not block other task on `Linux`, will continue other tasks until `enter` key is pressed,
     * Will block on Windows, once an key is typed/pressed, will continue other tasks `ONLY` if no key is pressed.
     * - This function needs to be prefixed with `yield`
     *
     * @return string
     */
    function input_wait(int $size = 256, bool $error = false)
    {
        return Coroutine::input($size, $error);
    }

    /**
     * Return the `string` of a variable type, or does a check, compared with string of the type.
     * Types are: `callable`, `string`, `int`, `float`, `null`, `bool`, `array`, `scalar`,
     * `object`, or `resource`
     *
     * @return string|bool
     */
    function is_type($variable, string $comparedWith = null)
    {
        $checks = [
            'is_callable' => 'callable',
            'is_string' => 'string',
            'is_integer' => 'int',
            'is_float' => 'float',
            'is_null' => 'null',
            'is_bool' => 'bool',
            'is_scalar' => 'scalar',
            'is_array' => 'array',
            'is_object' => 'object',
            'is_resource' => 'resource',
        ];

        foreach ($checks as $func => $val) {
            if ($func($variable)) {
                return (empty($comparedWith)) ? $val : ($comparedWith == $val);
            }
        }

        // @codeCoverageIgnoreStart
        return 'unknown';
        // @codeCoverageIgnoreEnd
    }

    function coroutine_instance(): ?CoroutineInterface
    {
        global $__coroutine__;

        return $__coroutine__;
    }

    function coroutine_clear()
    {
        global $__coroutine__;
        if ($__coroutine__ instanceof CoroutineInterface) {
            $__coroutine__->setup(false);
            unset($GLOBALS['__coroutine__']);
            $__coroutine__ = null;
        }
    }

    function coroutine_create(\Generator $routine = null)
    {
        $coroutine = \coroutine_instance();
        if (!$coroutine instanceof CoroutineInterface)
            $coroutine = new Coroutine();

        if (!empty($routine))
            $coroutine->createTask($routine);

        return $coroutine;
    }

    /**
     * This function runs the passed coroutine, taking care of managing the scheduler and
     * finalizing asynchronous generators. It should be used as a main entry point for programs, and
     * should ideally only be called once.
     *
     * @see https://docs.python.org/3.8/library/asyncio-task.html#asyncio.run
     *
     * @param Generator $routine
     * @param string $driver event loop driver to use, either `auto`, `uv`, or `stream_select`
     */
    function coroutine_run(\Generator $routine = null, ?string $driver = 'auto')
    {
        $coroutine = \coroutine_create($routine, $driver);

        if ($coroutine instanceof CoroutineInterface) {
            $coroutine->run();
            return true;
        }
    }

    /**
     * Modeled as in `Go` Language. The behavior of defer statements is straightforward and predictable.
     * There are three simple rules:
     * 1. *A deferred function's arguments are evaluated when the defer statement is evaluated.*
     * 2. *Deferred function calls are executed in Last In First Out order after the* surrounding function returns.
     * 3. *Deferred functions can`t modify return values when is type, but can modify content of reference to array or object.*
     *
     * PHP Limitations:
     * - In this *PHP* defer implementation,
     *  you cant modify returned value. You can modify only content of returned reference.
     * - You must always set first parameter in `defer` function,
     *  the parameter MUST HAVE same variable name as other `defer`,
     *  and this variable MUST NOT exist anywhere in local scope.
     * - You can`t pass function declared in local scope by name to *defer*.
     *
     * Modified from https://github.com/tito10047/php-defer
     *
     * @see https://golang.org/doc/effective_go.html#defer
     *
     * @param Defer|null $previous defer
     * @param callable $callback
     * @param mixed ...$args
     *
     * @throws \Exception
     */
    function defer(&$previous, $callback)
    {
        $args = \func_get_args();
        \array_shift($args);
        \array_shift($args);
        Defer::deferring($previous, $callback, $args);
    }

    /**
     * Modeled as in `Go` Language. Regains control of a panicking `task`.
     *
     * Recover is only useful inside `defer()` functions. During normal execution, a call to recover will return nil
     * and have no other effect. If the current `task` is panicking, a call to recover will capture the value given
     * to panic and resume normal execution.
     *
     * @param Defer|null $previous defer
     * @param callable $callback
     * @param mixed ...$args
     */
    function recover(&$previous, $callback)
    {
        $args = \func_get_args();
        \array_shift($args);
        \array_shift($args);
        Defer::recover($previous, $callback, $args);
    }

    /**
     * Modeled as in `Go` Language.
     *
     * An general purpose function for throwing an Coroutine `Exception`,
     * or some abnormal condition needing to keep an `Task` stack trace.
     */
    function panic($message = '', $code = 0, \Throwable $previous = null)
    {
        throw new Panic($message, $code, $previous);
    }

    /**
     * An PHP Functional Programming Primitive.
     *
     * Return a curryied version of the given function. You can decide if you also
     * want to curry optional parameters or not.
     *
     * @see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#currying
     *
     * @param callable $function the function to curry
     * @param bool $required curry optional parameters ?
     * @return callable a curryied version of the given function
     */
    function curry(callable $function, $required = true)
    {
        if (\method_exists('Closure', 'fromCallable')) {
            $reflection = new \ReflectionFunction(\Closure::fromCallable($function));
        } else {
            // @codeCoverageIgnoreStart
            if (\is_string($function) && \strpos($function, '::', 1) !== false) {
                $reflection = new \ReflectionMethod($function, null);
            } elseif (\is_array($function) && \count($function) === 2) {
                $reflection = new \ReflectionMethod($function[0], $function[1]);
            } elseif (\is_object($function) && \method_exists($function, '__invoke')) {
                $reflection = new \ReflectionMethod($function, '__invoke');
            } else {
                $reflection = new \ReflectionFunction($function);
            }
            // @codeCoverageIgnoreEnd
        }
        $count = $required ?
            $reflection->getNumberOfRequiredParameters() : $reflection->getNumberOfParameters();
        return \curry_n($count, $function);
    }

    /**
     * Return a version of the given function where the $count first arguments are curryied.
     *
     * No check is made to verify that the given argument count is either too low or too high.
     * If you give a smaller number you will have an error when calling the given function. If
     * you give a higher number, arguments will simply be ignored.
     *
     * @see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#curry_n
     *
     * @param int $count number of arguments you want to curry
     * @param callable $function the function you want to curry
     * @return callable a curryied version of the given function
     */
    function curry_n($count, callable $function)
    {
        $accumulator = function (array $arguments) use ($count, $function, &$accumulator) {
            return function (...$newArguments) use ($count, $function, $arguments, $accumulator) {
                $arguments = \array_merge($arguments, $newArguments);
                if ($count <= \count($arguments)) {
                    return \call_user_func_array($function, $arguments);
                }
                return $accumulator($arguments);
            };
        };
        return $accumulator([]);
    }
}
