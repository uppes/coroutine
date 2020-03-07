<?php

declare(strict_types=1);

namespace Async\Coroutine;

use Async\Coroutine\Kernel;
use Async\Coroutine\TaskInterface;
use Async\Coroutine\CoroutineInterface;

/**
 * Executes a blocking system call asynchronously.
 *
 * - All file system operations functions as defined by `libuv` are run in a **thread pool**.
 */
final class FileSystem
{
    /**
     * File access modes.
     *
     * @var array
     */
    protected static $fileFlags = array(
        'r' => \UV::O_RDONLY,
        'w' => \UV::O_WRONLY | \UV::O_CREAT,
        'a' => \UV::O_WRONLY | \UV::O_APPEND | \UV::O_CREAT,
        'r+' => \UV::O_RDWR,
        'w+' => \UV::O_RDWR | \UV::O_CREAT,
        'a+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_APPEND,
        'x' => \UV::O_WRONLY | \UV::O_CREAT | \UV::O_EXCL,
        'x+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_EXCL,
        'c' => \UV::O_WRONLY | \UV::O_CREAT | \UV::O_TRUNC,
        'c+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_TRUNC,
    );

    /**
     * Flag to control `UV` file operations.
     *
     * @var bool
     */
    protected static $useUV = true;

    /**
     * Check for UV and use for only file operations.
     *
     * @return bool
     */
    protected static function useUvFs(): bool
    {
        return \function_exists('uv_default_loop') && self::$useUV;
    }

    /**
     * Turn on UV for file operations, will use `libuv` **thread pool**.
     */
    public static function on()
    {
        self::$useUV = true;
    }

    /**
     * Turn off UV for file operations, will use system `child/subprocess`.
     */
    public static function off()
    {
        self::$useUV = false;
    }

    protected static function spawnStat($path, $info = null)
    {
        $result = yield \spawn_system('stat', $path);

        return empty($info) ? $result : $result[$info];
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
     * @codeCoverageIgnore
     *
     * @param string $from
     * @param string $to
     * @param int $flag
     */
    public static function symlink(string $from, string $to, int $flag)
    {
        if (FileSystem::useUvFs()) {
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
     * Attempts to create the directory specified by pathname.
     *
     * @param string $path
     * @param integer $mode
     * @param boolean $recursive
     */
    public static function mkdir(string $path, int $mode = 0777, $recursive = false)
    {
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
     * @codeCoverageIgnore
     *
     * @param string $path
     */
    public static function lstat(string $path)
    {
        if (FileSystem::useUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_lstat(
                        $coroutine->getUV(),
                        $path,
                        function (int $status, $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue(($status <= 0 ? (bool) $status: $result));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
        if (FileSystem::useUvFs()) {
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
                            $task->sendValue((\is_resource($fd) ? $result: (bool) $fd));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Read value of a symbolic link.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     */
    public static function readlink(string $path)
    {
        if (FileSystem::useUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_readlink(
                        $coroutine->getUV(),
                        $path,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue((!\is_resource($fd) ? (bool) $fd : $result));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Transfer data between file descriptors.
     *
     * @codeCoverageIgnore
     *
     * @param resource $out_fd
     * @param resource $in_fd
     * @param int $offset
     * @param int $length
     */
    public static function sendfile($out_fd, $in_fd, int $offset, int $length)
    {
        if (FileSystem::useUvFs()) {
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
    }

    /**
     * Open specified `$path` file with access `$flag`.
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
     */
    public static function open(string $path, string $flag, int $mode = 0)
    {
        if (isset(self::$fileFlags[$flag])) {
            if (FileSystem::useUvFs()) {
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
        }
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
        if (FileSystem::useUvFs()) {
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
    }

    /**
     * Write to file pointed to by the resource file descriptor
     *
     * @param resource $fd
     * @param string $buffer
     * @param int $offset
     */
    public static function write($fd, string $buffer, int $offset = -1)
    {
        if (FileSystem::useUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $buffer, $offset) {
                    $coroutine->fsAdd();
                    \uv_fs_write(
                        $coroutine->getUV(),
                        $fd,
                        $buffer,
                        $offset,
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
    }

    /**
     * Close file pointed to by the resource file descriptor.
     *
     * @param resource $fd
     */
    public static function close($fd)
    {
        if (FileSystem::useUvFs()) {
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
    }
}
