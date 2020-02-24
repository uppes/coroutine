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
 * - If `libuv` is not installed, all operations are run in a **child/subprocess** by
 * using `awaitProcess()`.
 *
 * @codeCoverageIgnore
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
        'w' => \UV::O_WRONLY | \UV::O_CREAT | \UV::O_TRUNC,
        'a' => \UV::O_WRONLY | \UV::O_APPEND | \UV::O_CREAT,
        'r+' => \UV::O_RDWR,
        'w+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_TRUNC,
        'a+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_APPEND,
        'x' => \UV::O_WRONLY | \UV::O_CREAT | \UV::O_EXCL,
        'x+' => \UV::O_RDWR | \UV::O_CREAT | \UV::O_EXCL,
    );

    protected static function justUvFs(): bool
    {
        return \function_exists('uv_default_loop');
    }

    /**
     * Renames a file or directory
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
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($from, $to, $context) {
            return \rename($from, $to, $context);
        });
    }

    /**
     * Deletes a file
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
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($path, $context) {
            return \unlink($path, $context);
        });
    }

    /**
     * Attempts to create the directory specified by pathname.
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
                        function ($fd, int $result) use ($task, $coroutine) {
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
     * Removes directory
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
                        function ($fd, int $result) use ($task, $coroutine) {
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
     * Changes file mode
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
                        function ($fd, int $result) use ($task, $coroutine) {
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

        return Kernel::awaitProcess(function () use ($fd, $offset) {
            return \ftruncate($fd, $offset);
        });
    }

    /**
     * Gives information about a file or symbolic link
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
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($path) {
            return \lstat($path);
        });
    }

    /**
     * Gives information about a file
     *
     * @param string $path
     */
    public static function stat(string $path)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($path) {
                    $coroutine->fsAdd();
                    \uv_fs_stat(
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

        return Kernel::awaitProcess(function () use ($path) {
            return \stat($path);
        });
    }

    /**
     * Gets information about a file using an open file pointer
     *
     * @param string $path
     */
    public static function fstat(string $fd)
    {
        if (FileSystem::justUvFs()) {
            return new Kernel(
                function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd) {
                    $coroutine->fsAdd();
                    \uv_fs_fstat(
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

        return Kernel::awaitProcess(function () use ($fd) {
            return \fstat($fd);
        });
    }

    /**
     * Read entry from directory
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
                        function ($fd, int $result) use ($task, $coroutine) {
                            $coroutine->fsRemove();
                            $task->sendValue($result);
                            $coroutine->schedule($task);
                        }
                    );
                }
            );
        }

        return Kernel::awaitProcess(function () use ($path) {
            return \readdir($path);
        });
    }

    /**
     * List files and directories inside the specified path
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

        return Kernel::awaitProcess(function () use ($path, $sorting_order, $context) {
            return \scandir($path, $sorting_order, $context);
        });
    }

    /**
     * Open specified file.
     * File access `$flag`. It can be:
     *
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
     *
     * @param string $path
     * @param string $flag either 'r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+'
     */
    public static function open(string $path, string $flag, int $mode = 0)
    {
        if (isset(self::$fileFlags[$flag])) {
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

    public static function read($fd, int $offset, int $length)
    {
        return new Kernel(
            function (TaskInterface $task, CoroutineInterface $coroutine) use ($fd, $offset, $length) {
                $coroutine->fsAdd();
                \uv_fs_read(
                    $coroutine->getUV(),
                    $fd,
                    $offset,
                    $length,
                    function ($fd, $data) use ($task, $coroutine) {
                        $coroutine->fsRemove();
                        $task->sendValue($data);
                        $coroutine->schedule($task);
                    }
                );
            }
        );
    }

    public static function write($fd, $buffer, $position, $callback)
    {
        uv_fs_write(uv_default_loop(), $fd, $buffer, $position, $callback);
    }

    public static function close($fd, $callback)
    {
        uv_fs_close(uv_default_loop(), $fd, $callback);
    }
}
