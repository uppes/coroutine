<?php

declare(strict_types=1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\Coroutine;
use Async\Coroutine\CoroutineInterface;

/**
 * Executes a blocking system call asynchronously.
 *
 * - All file system operations functions as defined by `libuv` are run in a **thread pool**.
 * - If `libuv` is not installed, or turned `off`, the file system operations are run in a **child/subprocess**.
 */
final class FileSystem
{
    /**
     * File access modes.
     *
     * @var array
     */
    protected static $fileFlags = array(
        'r' => \O_RDONLY,
        'w' => \O_WRONLY | \O_CREAT | \O_TRUNC,
        'a' => \O_WRONLY | \O_APPEND | \O_CREAT,
        'x' => \O_WRONLY | \O_CREAT | \O_EXCL,
        'c' => \O_WRONLY | \O_CREAT,
        'r+' => \O_RDWR,
        'w+' => \O_RDWR | \O_CREAT | \O_TRUNC,
        'a+' => \O_RDWR | \O_CREAT | \O_APPEND,
        'x+' => \O_RDWR | \O_CREAT | \O_EXCL,
        'c+' => \O_RDWR | \O_CREAT,
    );

    /**
     * Set of key => value pairs to include as default options/headers with `open` **uri** calls.
     */
    protected static $fileOpenUriContext = [
        'http' => [
            'method' => 'GET',
            'protocol_version' => '1.1',
            'follow_location' => 1,
            'request_fulluri' => false,
            'max_redirects' => 10,
            'ignore_errors' => true,
            'timeout' => 2,
            'user_agent' => 'Symplely Coroutine',
            'headers' => [
                'Accept' => '*/*',
                'Accept-Charset' => 'utf-8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'X-Powered-By' => 'PHP/' . \PHP_VERSION,
                'Connection' => 'close'
            ],
        ],
        'ssl' => [
            'disable_compression' => true
        ]
    ];

    /**
     * Flag to control `UV` file operations.
     *
     * @var bool
     */
    protected static $useUV = true;

    /**
     * Check for `libuv` and use for only file operations.
     *
     * @return bool
     */
    public static function isUv(): bool
    {
        return \IS_UV && self::$useUV;
    }

    /**
     * Setup how **Coroutine** handle file operations.
     *
     * @param bool $useUV
     * - `true` on - will use `libuv` by **thread pool**.
     * - `false` off - will use `uv_spawn` or PHP system `proc_open` by **child/subprocess**.
     */
    public static function setup(bool $useUV = true)
    {
        try {
            self::$useUV = $useUV;
        } catch (\Throwable $e) {
        }
    }

    protected static function spawnStat($path, string $info = null)
    {
        $result = yield \spawn_system('stat', $path);

        return (empty($info) || $info === null) ? $result : $result[$info];
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function spawnLstat($path, $info = null)
    {
        $result = yield \spawn_system('lstat', $path);

        return empty($info) ? $result : $result[$info];
    }

    protected static function fdStat($fd, $info = null)
    {
        $result = \fstat($fd);

        return yield \result((empty($info) ? $result : $result[$info]));
    }

    /**
     * Renames a file or directory.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public static function rename(string $from, string $to)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($from, $to) {
                    $coroutine->fsAdd();
                    \uv_fs_rename(
                        $coroutine->getUV(),
                        $from,
                        $to,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('rename', $from, $to);
    }

    /**
     * Sets access and modification time of file
     */
    public static function touch($path, $time = null, $atime = null)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $time, $atime) {
                    $time = empty($time) ? \uv_now() : $time;
                    $atime = empty($atime) ? \uv_now() : $atime;
                    $coroutine->fsAdd();
                    \uv_fs_utime(
                        $coroutine->getUV(),
                        $path,
                        $time,
                        $atime,
                        function (int $result) use ($task, $coroutine, $path) {
                            if ($result === 0) {
                                \uv_fs_open(
                                    $coroutine->getUV(),
                                    $path,
                                    self::$fileFlags['w'],
                                    0,
                                    function ($stream) use ($task, $coroutine) {
                                        \uv_fs_close(
                                            $coroutine->getUV(),
                                            $stream,
                                            function (bool $bool) use ($task, $coroutine) {
                                                $coroutine->fsRemove();
                                                $task->sendValue($bool);
                                                $coroutine->schedule($task);
                                            }
                                        );
                                    }
                                );
                            } else {
                                $coroutine->fsRemove();
                                $task->sendValue((bool) $result);
                                $coroutine->schedule($task);
                            }
                        }
                    );
                }
            );
        }

        return \spawn_system('touch', $path, $time, $atime);
    }

    /**
     * Deletes a file.
     *
     * @param string $path
     */
    public static function unlink(string $path)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_unlink(
                        $coroutine->getUV(),
                        $path,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('unlink', $path);
    }

    /**
     * Create a hard link.
     *
     * @codeCoverageIgnore
     *
     * @param string $from
     * @param string $to
     */
    public static function link(string $from, string $to)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($from, $to) {
                    $coroutine->fsAdd();
                    \uv_fs_link(
                        $coroutine->getUV(),
                        $from,
                        $to,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('link', $from, $to);
    }

    /**
     * Creates a symbolic link.
     *
     * @param string $from
     * @param string $to
     * @param int $flag
     */
    public static function symlink(string $from, string $to, int $flag = 0)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($from, $to, $flag) {
                    $coroutine->fsAdd();
                    \uv_fs_symlink(
                        $coroutine->getUV(),
                        $from,
                        $to,
                        $flag,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('symlink', $from, $to);
    }

    /**
     * Read value of a symbolic link.
     *
     * @param string $path
     */
    public static function readlink(string $path)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_readlink(
                        $coroutine->getUV(),
                        $path,
                        function (int $status, $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue(($status <= 0 ? (bool) $status : $result));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('readlink', $path);
    }

    /**
     * Attempts to create the directory specified by pathname.
     *
     * @param string $path
     * @param integer $mode
     * @param boolean $recursive
     */
    public static function mkdir(string $path, int $mode = 0777, $recursive = false)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $mode) {
                    $coroutine->fsAdd();
                    \uv_fs_mkdir(
                        $coroutine->getUV(),
                        $path,
                        $mode,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('mkdir', $path, $mode, $recursive);
    }

    /**
     * Removes directory.
     *
     * @param string $path
     */
    public static function rmdir(string $path)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_rmdir(
                        $coroutine->getUV(),
                        $path,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('rmdir', $path);
    }

    /**
     * Changes file mode.
     *
     * @codeCoverageIgnore
     *
     * @param string $filename
     * @param integer $mode
     */
    public static function chmod(string $filename, int $mode)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($filename, $mode) {
                    $coroutine->fsAdd();
                    \uv_fs_chmod(
                        $coroutine->getUV(),
                        $filename,
                        $mode,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('chmod', $filename, $mode);
    }

    /**
     * Changes file owner.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param int $uid
     * @param int $gid
     */
    public static function chown(string $path, int $uid, int $gid)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $uid, $gid) {
                    $coroutine->fsAdd();
                    \uv_fs_chown(
                        $coroutine->getUV(),
                        $path,
                        $uid,
                        $gid,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('chown', $path, $uid);
    }

    /**
     * Changes file owner by file descriptor.
     *
     * @codeCoverageIgnore
     *
     * @param resource $fd
     * @param int $uid
     * @param int $gid
     */
    public static function fchown(string $fd, int $uid, int $gid)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $uid, $gid) {
                    $coroutine->fsAdd();
                    \uv_fs_fchown(
                        $coroutine->getUV(),
                        $fd,
                        $uid,
                        $gid,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Changes file mode by file descriptor.
     *
     * @codeCoverageIgnore
     *
     * @param resource $fd
     * @param integer $mode
     */
    public static function fchmod(string $fd, int $mode)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $mode) {
                    $coroutine->fsAdd();
                    \uv_fs_fchmod(
                        $coroutine->getUV(),
                        $fd,
                        $mode,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Truncate a file to a specified offset by file descriptor.
     *
     * @codeCoverageIgnore
     *
     * @param resource $fd
     * @param int $offset
     *
     * @return void
     */
    public static function ftruncate($fd, int $offset)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $offset) {
                    $coroutine->fsAdd();
                    \uv_fs_ftruncate(
                        $coroutine->getUV(),
                        $fd,
                        $offset,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((\is_resource($fd) ? $result : false));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Synchronize a file's in-core state with storage device by file descriptor.
     *
     * @codeCoverageIgnore
     *
     * @param resource $fd
     */
    public static function fsync($fd)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd) {
                    $coroutine->fsAdd();
                    \uv_fs_fsync(
                        $coroutine->getUV(),
                        $fd,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((\is_resource($fd) ? $result : false));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Synchronize a file's in-core state with storage device by file descriptor.
     *
     * @param resource $fd
     */
    public static function fdatasync($fd)
    {
        if (self::isUv() && (self::meta($fd, 'wrapper_type') !== 'http')) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd) {
                    $coroutine->fsAdd();
                    \uv_fs_fdatasync(
                        $coroutine->getUV(),
                        $fd,
                        function ($fd) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((\is_resource($fd) ? $fd : false));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Gives information about a file symbolic link, returns same data as `stat()`
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
     * 11	blksize	blocksize of filesystem IO **
     * 12	blocks	number of 512-byte blocks allocated **
     *````
     */
    public static function lstat(string $path, ?string $info = null)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $info) {
                    $coroutine->fsAdd();
                    \uv_fs_lstat(
                        $coroutine->getUV(),
                        $path,
                        function (int $status, $result) use ($task, $coroutine, $info) {
                            $coroutine->fsRemove();
                            $task->sendValue(
                                ($status <= 0
                                    ? (bool) $status
                                    : (isset($result[$info])
                                        ? $result[$info]
                                        : $result))
                            );

                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return self::spawnLstat($path, $info);
    }

    /**
     * Gives information about a file.
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
     * 11	blksize	blocksize of filesystem IO **
     * 12	blocks	number of 512-byte blocks allocated **
     *````
     */
    public static function stat(string $path, ?string $info = null)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $info) {
                    $coroutine->fsAdd();
                    \uv_fs_stat(
                        $coroutine->getUV(),
                        $path,
                        function (bool $status, $result) use ($task, $coroutine, $info) {
                            $coroutine->fsRemove();
                            $task->sendValue(
                                ($status <= 0
                                    ? (bool) $status
                                    : (isset($result[$info])
                                        ? $result[$info]
                                        : $result))
                            );

                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return self::spawnStat($path, $info);
    }

    /**
     * Gets information about a file using an open file pointer.
     *
     * @param resource $fd
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
     * 11	blksize	blocksize of filesystem IO **
     * 12	blocks	number of 512-byte blocks allocated **
     *````
     */
    public static function fstat($fd, ?string $info = null)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $info) {
                    $coroutine->fsAdd();
                    \uv_fs_fstat(
                        $coroutine->getUV(),
                        $fd,
                        function ($fd, $result) use ($task, $coroutine, $info) {
                            $coroutine->fsRemove();
                            $task->sendValue(
                                (!\is_resource($fd)
                                    ? (bool) $fd
                                    : (isset($result[$info])
                                        ? $result[$info]
                                        : $result))
                            );

                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return self::fdStat($fd, $info);
    }

    /**
     * Read entry from directory.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param integer $flag
     * @return void
     */
    public static function readDir(string $path, int $flag = 0)
    {
        if (self::isUv()) {
            return self::scandir($path, $flag);
        }

        return \spawn_system('readdir', $path);
    }

    /**
     * List files and directories inside the specified path.
     *
     * @param string $path
     * @param mixed $flagSortingOrder
     */
    public static function scandir(string $path, int $flagSortingOrder = 0)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $flagSortingOrder) {
                    $coroutine->fsAdd();
                    \uv_fs_scandir(
                        $coroutine->getUV(),
                        $path,
                        $flagSortingOrder,
                        function (int $status, $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue(($status <= 0 ? (bool) $status : $result));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return \spawn_system('scandir', $path, $flagSortingOrder);
    }

    /**
     * Change file last access and modification times.
     *
     * @param string $path
     * @param int $utime
     * @param int $atime
     */
    public static function utime(string $path, int $utime = null, int $atime = null)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $utime, $atime) {
                    $coroutine->fsAdd();
                    $utime = empty($utime) ? \uv_now() : $utime;
                    $atime = empty($atime) ? \uv_now() : $atime;
                    \uv_fs_utime(
                        $coroutine->getUV(),
                        $path,
                        $utime,
                        $atime,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((bool) $result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Monitor/watch the specified path for changes,
     * switch to a `monitor_task()` by id to handle any changes.
     * - The `monitor_task` will receive `(?string $filename, int $events, int $status)`.
     * - This function needs to be prefixed with `yield`
     *
     * @param string $path
     * @param integer $monitorTask
     *
     * @return bool
     */
    public static function monitor(string $path, int $monitorTask)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $monitorTask) {
                    $fsEvent = null;
                    $changedTask = $coroutine->taskInstance($monitorTask);
                    if ($changedTask instanceof TaskInterface) {
                        $coroutine->fsAdd();
                        $fsEvent = \uv_fs_event_init(
                            $coroutine->getUV(),
                            $path,
                            function ($rsc, $name, $event, $status) use ($monitorTask, $coroutine) {
                                $changedTask = $coroutine->taskInstance($monitorTask);
                                if ($changedTask instanceof TaskInterface) {
                                    $changedTask->sendValue([$name, $event, $status]);
                                    $coroutine->schedule($changedTask);
                                }
                            },
                            4
                        );

                        $changedTask->customData($fsEvent);
                        $changedTask->customState($path);
                        $changedTask->taskType('monitored');
                    }

                    $task->sendValue($fsEvent instanceof \UVFsEvent);
                    $coroutine->schedule($task);
                }
            );
        }

        return \result(false);
    }

    /**
     * change file timestamps using file descriptor.
     *
     * @codeCoverageIgnore
     *
     * @param string $fd
     * @param int $utime
     * @param int $atime
     */
    public static function futime(string $fd, int $utime, int $atime)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $utime, $atime) {
                    $coroutine->fsAdd();
                    \uv_fs_futime(
                        $coroutine->getUV(),
                        $fd,
                        $utime,
                        $atime,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((\is_resource($fd) ? $result : (bool) $fd));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    protected static function send($out_fd, $in_fd, int $offset = 0, int $length = 8192)
    {
        if (!\is_resource($out_fd) || !\is_resource($in_fd)) {
            return yield \result(false);
        }

        $data = yield self::read($in_fd, $offset, $length);
        $count = \strlen($data);
        if ($count) {
            $result = yield self::write($out_fd, $data);
            if (false === $result) {
                return yield \result(false);
            }

            @\rewind($out_fd);
            yield Coroutine::value($count);
        }
    }

    /**
     * Transfer data between file descriptors.
     *
     * @param resource $out_fd
     * @param resource $in_fd
     * @param int $offset
     * @param int $length
     */
    public static function sendfile($out_fd, $in_fd, int $offset = 0, int $length = 8192)
    {
        if (self::isUv()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($out_fd, $in_fd, $offset, $length) {
                    $coroutine->fsAdd();
                    \uv_fs_sendfile(
                        $coroutine->getUV(),
                        $out_fd,
                        $in_fd,
                        $offset,
                        $length,
                        function ($out_fd, $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((!\is_resource($out_fd) ? (bool) $out_fd : $result));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return self::send($out_fd, $in_fd, $offset, $length);
    }

    /**
     * Open specified `$path` file with access `$flag`.
     *
     * @param string $path
     * @param string $flag either **`r`, `r+`, `w`, `w+`, `a`, `a+`, `x`, `x+`, `c`, `c+`**:
     * - "`r`"	`read`: Open file for input operations. The file must exist.
     * - "`w`"	`write`: Create an empty file for output operations.
     * If a file with the same name already exists, its contents are discarded and the
     * file is treated as a new empty file.
     * - "`a`"	`append`: Open file for output at the end of a file.
     * Output operations always write data at the end of the file, expanding it.
     * Repositioning operations (fseek, fsetpos, rewind) are ignored.
     * The file is created if it does not exist.
     * - "`x`" `Write only`: Creates a new file. Returns `FALSE` and an error if file already exists.
     * - "`c`" 	Open the file for writing only. If the file does not exist, it is created. If it exists,
     * it is neither truncated (as opposed to "`w`"), nor the call to this function fails (as is the case
     * with "`x`"). The file pointer is positioned on the beginning of the file.
     * - "`r+`" `read/update`: Open a file for update (both for input and output). The file must exist.
     * - "`w+`" `write/update`: Create an empty file and open it for update (both for input and output).
     * If a file with the same name already exists its contents are discarded and the file is
     * treated as a new empty file.
     * - "`a+`" `append/update`: Open a file for update (both for input and output) with all output
     * operations writing data at the end of the file. Repositioning operations (fseek, fsetpos,
     * rewind) affects the next input operations, but output operations move the position back
     * to the end of file. The file is created if it does not exist.
     * - "`x+`" `Read/Write`: Creates a new file. Returns `FALSE` and an error if file already exists.
     * - "`c+`" Open the file for reading and writing; otherwise it has the same behavior as "`c`".
     * @param int $mode — this should be `S_IRWXU` and some mode flag, `libuv` only.
     * @param resource|array|null $contexts not for `libuv`.
     */
    public static function open(string $path, string $flag, int $mode = \S_IRWXU, $contexts = null)
    {
        if (isset(self::$fileFlags[$flag])) {
            if (self::isUv() && (\strpos($path, '://') === false)) {
                return new Kernel(
                    function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $flag, $mode) {
                        $coroutine->fsAdd();
                        \uv_fs_open(
                            $coroutine->getUV(),
                            $path,
                            self::$fileFlags[$flag],
                            $mode,
                            function ($stream) use ($task, $coroutine) {
                                $coroutine->fsRemove();
                                $task->sendValue((\is_resource($stream) ? $stream : false));
                                $coroutine->schedule($task);
                            }
                        );
                    }
                );
            }

            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $flag, $contexts) {
                    $ctx = null;
                    if (\strpos($path, '://') !== false && \strpos($path, 'php://') === false) {
                        $ctx = !\is_resource($contexts)
                            ? \stream_context_create(\array_merge(self::$fileOpenUriContext, (array) $contexts))
                            : $contexts;
                    }

                    if (\is_resource($ctx)) {
                        $resource = @\fopen($path, $flag . 'b', false, $ctx);
                    } else {
                        $resource = @\fopen($path, $flag . 'b');
                    }

                    if (\is_resource($resource)) {
                        \stream_set_blocking($resource, false);
                        \stream_set_read_buffer($resource, 0);
                        \stream_set_write_buffer($resource, 0);
                    }

                    $task->sendValue((\is_resource($resource) ? $resource : false));
                    $coroutine->schedule($task);
                }
            );
        }

        return \result(false);
    }

    protected static function readFile($fd, $offset = null, $length = null)
    {
        yield;
        yield Kernel::readWait($fd, true);
        $contents = \stream_get_contents($fd, $length, $offset);

        return $contents;
    }

    /**
     * Read file pointed to by the resource file descriptor
     *
     * @param resource $fd
     * @param int $offset
     * @param int $length
     */
    public static function read($fd, int $offset = 0, int $length = 8192)
    {
        if (self::isUv() && (self::meta($fd, 'wrapper_type') !== 'http')) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $offset, $length) {
                    $coroutine->fsAdd();
                    \uv_fs_read(
                        $coroutine->getUV(),
                        $fd,
                        $offset,
                        $length,
                        function ($fd, $status, $data) use ($task, $coroutine) {
                            $data = $status == 0 ? '' : $data;
                            if ($status < 0) {
                                // @codeCoverageIgnoreStart
                                \uv_fs_close($coroutine->getUV(), $fd, function () use ($task, $coroutine) {
                                    $coroutine->fsRemove();
                                    $task->setException(new \Exception("read error"));
                                    $coroutine->schedule($task);
                                });
                                // @codeCoverageIgnoreEnd
                            } else {
                                $coroutine->fsRemove();
                                $task->sendValue($data);
                                $coroutine->schedule($task);
                            }
                        }
                    );
                }
            );
        }

        return self::readFile($fd, $offset, $length);
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function writeFile($fd, string $buffer, $immediately = false)
    {
        yield;
        $fwrite = 0;
        for ($written = 0; $written < \strlen($buffer); $written += $fwrite) {
            yield Kernel::writeWait($fd, (\is_bool($immediately) ? $immediately : false));
            $fwrite = \fwrite($fd, \substr($buffer, $written));
            // see https://www.php.net/manual/en/function.fwrite.php#96951
            if (($fwrite === false) || ($fwrite == 0)) {
                break;
            }
        }

        return $written;
    }

    /**
     * Write to file pointed to by the resource file descriptor
     *
     * @param resource $fd
     * @param string $buffer
     * @param int|bool $offset if not `UV` set to schedule immediately
     */
    public static function write($fd, string $buffer, $offset = -1)
    {
        if (self::isUv() && (self::meta($fd, 'wrapper_type') !== 'http')) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $buffer, $offset) {
                    $coroutine->fsAdd();
                    \uv_fs_write(
                        $coroutine->getUV(),
                        $fd,
                        $buffer,
                        (\is_int($offset) ? $offset : -1),
                        function ($fd, int $result) use ($task, $coroutine) {
                            if ($result < 0) {
                                // @codeCoverageIgnoreStart
                                \uv_fs_close($coroutine->getUV(), $fd, function () use ($task, $coroutine) {
                                    $coroutine->fsRemove();
                                    $task->setException(new \Exception("write error"));
                                    $coroutine->schedule($task);
                                });
                                // @codeCoverageIgnoreEnd
                            } else {
                                $coroutine->fsRemove();
                                $task->sendValue($result);
                                $coroutine->schedule($task);
                            }
                        }
                    );
                }
            );
        }

        return self::writeFile($fd, $buffer, $offset);
    }

    protected static function closeFile($fd)
    {
        yield;
        return \is_resource($fd) ? @\fclose($fd) : false;
    }

    /**
     * Close file pointed to by the resource file descriptor.
     *
     * @param resource $fd
     */
    public static function close($fd)
    {
        if (!\is_resource($fd))
            return \result(false);

        if (self::isUv() && (self::meta($fd, 'wrapper_type') !== 'http')) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd) {
                    $coroutine->fsAdd();
                    \uv_fs_close(
                        $coroutine->getUV(),
                        $fd,
                        function (bool $bool) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($bool);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return self::closeFile($fd);
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
    public static function meta($fd, ?string $info = null)
    {
        if (!\is_resource($fd) && ($info == 'status' || $info == 'size'))
            return $info == 'status' ? 400 : 0;
        elseif (!\is_resource($fd))
            return false;

        $meta = \stream_get_meta_data($fd);
        if ($info == 'status' && isset($meta['wrapper_data'])) {
            $http_statusCode = 400;
            foreach ($meta['wrapper_data'] as $headerLine) {
                if (\preg_match('/^HTTP\/(\d+\.\d+)\s+(\d+)\s*(.+)?$/', $headerLine, $result)) {
                    $http_statusCode = (int) $result[2];
                }
            }

            return $http_statusCode;
        }

        if ($info == 'size' && isset($meta['wrapper_data'])) {
            $http_contentLength = 0;
            foreach ($meta['wrapper_data'] as $headerLine) {
                if (\preg_match('/Content-Length: (\d+)/', $headerLine, $result)) {
                    $http_contentLength = (int) $result[1];
                }
            }

            return $http_contentLength;
        }

        return isset($meta[$info]) ? $meta[$info] : $meta;
    }

    /**
     * Reads remainder of a stream/file pointer by size into a string,
     * will stop if timeout seconds lapse.
     *
     * @param resource $fd
     * @param integer $size
     * @param float $timeout_seconds
     */
    public static function contents($fd, int $size = 256, float $timeout_seconds = 0.5)
    {
        if (!\is_resource($fd))
            return yield \result(false);

        $contents = '';
        while (true) {
            yield Kernel::readWait($fd);
            $startTime = \microtime(true);
            $new = \stream_get_contents($fd, $size);
            $endTime = \microtime(true);
            if (\is_string($new) && \strlen($new) >= 1) {
                $contents .= $new;
            }

            $time_used = $endTime - $startTime;
            if (($time_used >= $timeout_seconds)
                || !\is_string($new) || (\is_string($new) && \strlen($new) < 1)
            ) {
                break;
            }
        }

        yield Coroutine::value($contents);
    }
}
