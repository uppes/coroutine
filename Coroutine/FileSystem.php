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
 * - @todo If `libuv` is not installed, all operations are run in a **child/subprocess**.
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
        'w+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_TRUNC,
        'a+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_APPEND,
        'x' => \UV::O_WRONLY | \UV::O_CREAT | \UV::O_EXCL,
        'x+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_EXCL,
    );

    /**
     * Check for UV for only file operations.
     *
     * @return bool
     */
    protected static function justUvFs(): bool
    {
        return \function_exists('uv_default_loop');
    }

    /**
     * Renames a file or directory.
     *
     * @codeCoverageIgnore
     *
     * @param string $from
     * @param string $to
     * @param mixed $context
     */
    public static function rename(string $from, string $to, $context = null)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($from, $to) {
                    $coroutine->fsAdd();
                    \uv_fs_rename(
                        $coroutine->getUV(),
                        $from,
                        $to,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Deletes a file.
     *
     * @param string $path
     * @param mixed $context
     */
    public static function unlink(string $path, $context = null)
    {
        if (FileSystem::justUvFs()) {
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
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($from, $to) {
                    $coroutine->fsAdd();
                    \uv_fs_link(
                        $coroutine->getUV(),
                        $from,
                        $to,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($from, $to) {
            return \link($from, $to);
        });
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
        if (FileSystem::justUvFs()) {
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
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($from, $to) {
            return \symlink($from, $to);
        });
    }

    /**
     * Attempts to create the directory specified by pathname.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param integer $mode
     * @param boolean $recursive
     * @param mixed $context
     */
    public static function mkdir(string $path, int $mode = 0777, $recursive = false, $context = null)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $mode) {
                    $coroutine->fsAdd();
                    \uv_fs_mkdir(
                        $coroutine->getUV(),
                        $path,
                        $mode,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($path, $mode, $recursive, $context) {
            return \mkdir($path, $mode, $recursive, $context);
        });
    }
    /**
     * Removes directory.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param mixed $context
     */
    public static function rmdir(string $path, $context = null)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_rmdir(
                        $coroutine->getUV(),
                        $path,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($path, $context) {
            return \rmdir($path, $context);
        });
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
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($filename, $mode) {
                    $coroutine->fsAdd();
                    \uv_fs_chmod(
                        $coroutine->getUV(),
                        $filename,
                        $mode,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($filename, $mode) {
            return \chmod($filename, $mode);
        });
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
        if (FileSystem::justUvFs()) {
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
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($path, $uid, $gid) {
            return \chown($path, $uid);
        });
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
        if (FileSystem::justUvFs()) {
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
                            $task->sendValue($result);
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
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $mode) {
                    $coroutine->fsAdd();
                    \uv_fs_fchmod(
                        $coroutine->getUV(),
                        $fd,
                        $mode,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
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
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $offset) {
                    $coroutine->fsAdd();
                    \uv_fs_ftruncate(
                        $coroutine->getUV(),
                        $fd,
                        $offset,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
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
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd) {
                    $coroutine->fsAdd();
                    \uv_fs_fsync(
                        $coroutine->getUV(),
                        $fd,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
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
    public static function fdatasync($fd)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd) {
                    $coroutine->fsAdd();
                    \uv_fs_fdatasync(
                        $coroutine->getUV(),
                        $fd,
                        function ($fd) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($fd);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Gives information about a file or symbolic link.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     */
    public static function lstat(string $path)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_lstat(
                        $coroutine->getUV(),
                        $path,
                        function (int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
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
     */
    public static function stat(string $path, ?string $info = null)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $info) {
                    $coroutine->fsAdd();
                    \uv_fs_stat(
                        $coroutine->getUV(),
                        $path,
                        function (bool $status, $result) use ($task, $coroutine, $info) {
                            $coroutine->fsRemove();
                            $task->sendValue((isset($result[$info]) ? $result[$info] : $result));
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * Gets information about a file using an open file pointer.
     *
     * @param resource $fd
     */
    public static function fstat($fd, ?string $info = null)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $info) {
                    $coroutine->fsAdd();
                    \uv_fs_fstat(
                        $coroutine->getUV(),
                        $fd,
                        function ($fd, $result) use ($task, $coroutine, $info) {
                            $coroutine->fsRemove();
                            $task->sendValue((isset($result[$info]) ? $result[$info] : $result));
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
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $flag) {
                    $coroutine->fsAdd();
                    \uv_fs_readdir(
                        $coroutine->getUV(),
                        $path,
                        $flag,
                        function ($result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    /**
     * List files and directories inside the specified path.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param integer $flag
     * @param mixed $sorting_order
     * @param mixed $context
     */
    public static function scandir(string $path, int $flag = 0, $sorting_order = null, $context = null)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $flag) {
                    $coroutine->fsAdd();
                    \uv_fs_scandir(
                        $coroutine->getUV(),
                        $path,
                        function ($result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        },
                        $flag
                    );
                }
            );
        }
    }

    /**
     * Change file last access and modification times.
     *
     * @codeCoverageIgnore
     *
     * @param string $path
     * @param int $utime
     * @param int $atime
     */
    public static function utime(string $path, int $utime, int $atime)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path, $utime, $atime) {
                    $coroutine->fsAdd();
                    \uv_fs_utime(
                        $coroutine->getUV(),
                        $path,
                        $utime,
                        $atime,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
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
        if (FileSystem::justUvFs()) {
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
                            $task->sendValue($result);
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
     * @param int $utime
     * @param int $atime
     */
    public static function readlink(string $path, int $utime, int $atime)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_readlink(
                        $coroutine->getUV(),
                        $path,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
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
        if (FileSystem::justUvFs()) {
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
                            $task->sendValue($result);
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
            if (FileSystem::justUvFs()) {
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
                                $task->sendValue($stream);
                                $coroutine->schedule($task);
                            }
                        );
                    }
                );
            }
        }
    }

    public static function read($fd, int $offset, int $length)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $offset, $length) {
                    $coroutine->fsAdd();
                    \uv_fs_read(
                        $coroutine->getUV(),
                        $fd,
                        $offset,
                        $length,
                        function ($fd, $status, $data) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            // @codeCoverageIgnoreStart
                            if ($status <= 0) {
                                if ($status < 0) {
                                    $task->setException(new \Exception("read error"));
                                }

                                \uv_fs_close($coroutine->getUV(), $fd, function () {
                                });
                                // @codeCoverageIgnoreEnd
                            } else {
                                $task->sendValue($data);
                            }

                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    public static function write($fd, $buffer, $position = -1)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $buffer, $position) {
                    $coroutine->fsAdd();
                    \uv_fs_write(
                        $coroutine->getUV(),
                        $fd,
                        $buffer,
                        $position,
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }
    }

    public static function close($fd)
    {
        if (FileSystem::justUvFs()) {
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
