<?php

namespace Async\Tests;

use function Async\Path\{
    file_open,
    file_read,
    file_fdatasync,
    file_contents,
    file_close,
    file_write,
    file_delete,
    file_unlink,
    file_exist,
    file_size,
    file_touch,
    file_put,
    file_readlink,
    file_fstat,
    file_rmdir,
    file_rename,
    file_symlink,
    file_mkdir,
    file_get,
    file_operation,
    file_scandir,
    file_sendfile,
    file_lstat,
    file_uri,
    file_meta,
    file_file,
    monitor_dir,
    monitor_task
};
use function Async\Worker\spawn_system;

use Async\Coroutine\FileSystem;
use Async\Coroutine\Exceptions\Panic;
use PHPUnit\Framework\TestCase;

class FileSystemTest extends TestCase
{
    protected $counterResult = null;

    protected function setUp(): void
    {
        \coroutine_clear();
        if (!defined('FIXTURE_PATH'))
            \define("FIXTURE_PATH", dirname(__FILE__) . \DS . "libuv" . \DS . "fixtures" . \DS . "hello.data");
        if (!defined('FIXTURES'))
            \define("FIXTURES", dirname(__FILE__) . \DS . "libuv" . \DS . "fixtures" . \DS);
        if (!defined('DIRECTORY_PATH'))
            \define("DIRECTORY_PATH", dirname(__FILE__) . \DS . "libuv" . \DS . "fixtures" . \DS . "example_directory");
        @rmdir(DIRECTORY_PATH);
    }

    public function counterTask()
    {
        $counter = 0;
        while (true) {
            $counter++;
            $this->counterResult = $counter;
            yield;
        }
    }

    public function taskOpenRead()
    {
        yield \away($this->counterTask());
        $fd = yield file_open(FIXTURE_PATH, 'r');
        $this->assertEquals('resource', \is_type($fd));

        $data = yield file_read($fd, 0, 32);
        $this->assertEquals('string', \is_type($data));

        $this->assertEquals('Hello', \rtrim($data));
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $bool = yield file_close($fd);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(4, $this->counterResult);
        yield \shutdown();
    }

    public function testOpenRead()
    {
        \coroutine_run($this->taskOpenRead());
    }

    public function taskOpenReadOffsetFstat()
    {
        yield \away($this->counterTask());
        $fd = yield file_open(FIXTURE_PATH, 'r');
        $this->assertEquals('resource', \is_type($fd));

        $size = yield file_fstat($fd, 'size');
        $this->assertEquals('int', \is_type($size));
        $this->assertGreaterThanOrEqual(2, $this->counterResult);

        $data = yield file_read($fd, 1, $size);
        $this->assertEquals('string', \is_type($data));

        $this->assertEquals('ello', \rtrim($data));
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $bool = yield file_close($fd);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(4, $this->counterResult);
        yield \shutdown();
    }

    public function testOpenReadOffsetFstat()
    {
        \coroutine_run($this->taskOpenReadOffsetFstat());
    }

    public function taskWrite()
    {
        yield \away($this->counterTask());
        $fd = yield file_open("./temp", 'a');
        $this->assertEquals('resource', \is_type($fd));

        $data = yield file_write($fd, "hello");
        $this->assertEquals('int', \is_type($data));

        $this->assertEquals(5, $data);
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        if (\IS_UV) {
            $fd = yield file_fdatasync($fd);
            $this->assertEquals('resource', \is_type($fd));
            $this->assertGreaterThanOrEqual(8, $this->counterResult);

            $bool = yield file_close($fd);
            $this->assertTrue($bool);
            $this->assertGreaterThanOrEqual(9, $this->counterResult);

            $size = yield file_size("./temp");
            $this->assertEquals('int', \is_type($size));
            $this->assertGreaterThanOrEqual(10, $this->counterResult);

            $bool = yield file_rename("./temp", "./tmpNew");
            $this->assertTrue($bool);

            $bool = yield file_touch('./tmpNew');
            $this->assertTrue($bool);
            $this->assertGreaterThanOrEqual(12, $this->counterResult);

            $bool = yield file_unlink("./tmpNew");
            $this->assertTrue($bool);
            $this->assertGreaterThanOrEqual(13, $this->counterResult);

            $bool = yield file_mkdir(DIRECTORY_PATH);
            $this->assertTrue($bool);
            $this->assertGreaterThanOrEqual(14, $this->counterResult);

            $bool = yield file_rmdir(DIRECTORY_PATH);
            $this->assertTrue($bool);
            $this->assertGreaterThanOrEqual(15, $this->counterResult);
        }

        $fd = yield file_open("tmp", 'bad');
        $this->assertFalse($fd);

        yield \shutdown();
    }

    public function testWrite()
    {
        \coroutine_run($this->taskWrite());
    }

    public function taskFilePut()
    {
        $contents1 = "put test";
        $new = FIXTURES . "put.txt";

        $count = yield file_put($new, $contents1);
        $this->assertEquals(8, $count);

        $contents2 = yield file_get($new);

        $this->assertSame($contents1, $contents2);

        yield file_unlink($new);
        yield \shutdown();
    }

    public function testFilePut()
    {
        \coroutine_run($this->taskFilePut());
    }

    public function taskFileLink()
    {
        $original = FIXTURES . "link.txt";
        $link = FIXTURES . "symlink.txt";

        $bool = yield file_symlink($original, $link);
        $this->assertTrue($bool);

        $array = yield file_lstat($link);
        $this->assertIsArray($array);

        $result = yield file_readlink($link);
        $this->assertSame($original, $result);

        yield file_unlink($link);
        yield \shutdown();
    }

    public function testFileLink()
    {
        if (!\function_exists('uv_loop_new'))
            $this->markTestSkipped('Test skipped "uv_loop_new" missing.');

        \coroutine_run($this->taskFileLink());
    }

    public function taskFileContents()
    {
        yield \away($this->counterTask());

        $data = yield file_contents(null);
        $this->assertFalse($data);

        $text = \str_repeat('abcde', 256);
        $fd = yield file_open('php://temp', 'w+');
        $written = yield file_write($fd, $text);
        yield file_fdatasync($fd);
        $this->assertEquals(\strlen($text), $written);

        $data = yield file_contents($fd);
        if (!\IS_PHP8)
            $this->assertEquals($text, $data);

        $this->assertGreaterThanOrEqual(\IS_PHP8 ? 5 : 8, $this->counterResult);

        $moreData = yield file_contents($fd);
        $this->assertEquals('', $moreData);

        $this->assertGreaterThanOrEqual(\IS_PHP8 ? 6 : 9, $this->counterResult);

        $bool = yield file_close($fd);
        $this->assertTrue($bool);

        yield \shutdown();
    }

    public function testFileContents()
    {
        \coroutine_run($this->taskFileContents());
    }

    public function taskFileSystem()
    {
        file_operation();
        yield \away($this->counterTask());
        $bool = yield file_touch('./tmpTouch');
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(5, $this->counterResult);

        $size = yield file_size("./tmpTouch");
        $this->assertEquals(0, $size);
        $this->assertGreaterThanOrEqual(7, $this->counterResult);

        $bool = yield file_exist("./tmpTouch");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(10, $this->counterResult);

        $bool = yield file_rename("./tmpTouch", "./tmpRename");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(13, $this->counterResult);

        $bool = yield file_unlink("./tmpRename");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(16, $this->counterResult);

        $bool = yield file_mkdir(DIRECTORY_PATH);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(19, $this->counterResult);

        $bool = yield file_rmdir(DIRECTORY_PATH);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(25, $this->counterResult);

        file_operation(true);
        $bool = yield file_touch("./tmpNew");
        $this->assertTrue($bool);
        $result = yield FileSystem::utime("./tmpNew");
        $this->assertTrue($result);
        $bool = yield file_unlink("./tmpNew");
        $this->assertTrue($bool);

        yield \shutdown();
    }

    public function testFileSystem()
    {
        \coroutine_run($this->taskFileSystem());
    }

    public function taskFileSystemScandir()
    {
        yield \away($this->counterTask());
        $array = yield file_scandir('.');
        $this->assertTrue(\is_array($array));
        $this->assertTrue(\count($array) > 1);
        $this->assertGreaterThanOrEqual(2, $this->counterResult);

        yield \shutdown();
    }

    public function testFileSystemScandir()
    {
        \coroutine_run($this->taskFileSystemScandir());
    }

    public function taskFileSystemSendfile()
    {
        yield \away($this->counterTask());
        $fd = yield file_open(FIXTURE_PATH, 'r');
        $size = yield file_fstat($fd, 'size');
        $outFd = yield file_open('php://temp', 'w+');
        $written = yield file_sendfile($outFd, $fd, 0, $size);

        $this->assertEquals($size, $written);
        $data = yield file_contents($outFd);
        $this->assertEquals('Hello', \trim($data));
        $this->assertGreaterThanOrEqual(6, $this->counterResult);
        yield file_close($fd);
        yield file_close($outFd);

        yield \shutdown();
    }

    public function testFileSystemSendfile()
    {
        \coroutine_run($this->taskFileSystemSendfile());
    }

    public function taskFileSendfile()
    {
        file_operation();
        yield \away($this->counterTask());
        $fd = yield file_open(FIXTURE_PATH, 'r');
        $size = yield file_fstat($fd, 'size');
        $outFd = yield file_open('php://temp', 'w+');
        $written = yield file_sendfile($outFd, $fd, 0, $size);
        $this->assertEquals($size, $written);
        $data = yield file_contents($outFd);
        $this->assertEquals('Hello', \trim($data));
        $this->assertGreaterThanOrEqual(7, $this->counterResult);
        yield file_close($fd);
        yield file_close($outFd);

        yield \shutdown();
    }

    public function testFileSendfile()
    {
        \coroutine_run($this->taskFileSendfile());
    }

    public function taskSystemScandir()
    {
        file_operation();
        yield \away($this->counterTask());
        $array = yield file_scandir('.');
        $this->assertTrue(\is_array($array));
        $this->assertTrue(\count($array) > 1);
        $this->assertGreaterThanOrEqual(5, $this->counterResult);

        yield \shutdown();
    }

    public function testSystemScandir()
    {
        \coroutine_run($this->taskSystemScandir());
    }

    public function taskSystemError()
    {
        $this->expectException(Panic::class);
        yield spawn_system('/');

        yield \shutdown();
    }

    public function testSystemError()
    {
        \coroutine_run($this->taskSystemError());
    }

    public function taskFileGet()
    {
        $contents = yield file_get('.' . \DS . 'list.txt');
        $this->assertTrue(\is_type($contents, 'bool'));
        $contents = yield file_get(__DIR__ . \DS . 'list.txt');
        $this->assertEquals('string', \is_type($contents));

        yield \shutdown();
    }

    public function testFileGet()
    {
        \coroutine_run($this->taskFileGet());
    }


    public function taskFileGetSize()
    {
        $contents = yield file_get("https://httpbin.org/get");
        $this->assertEquals('string', \is_type($contents));
        $this->assertGreaterThanOrEqual(230, \strlen($contents));
        $fd = yield file_uri("https://httpbin.org/get");
        $this->assertTrue(\is_resource($fd));
        $size = file_meta($fd, 'size');
        $this->assertGreaterThanOrEqual(230, $size);
        $bool = yield file_close($fd);
        $this->assertTrue($bool);
        $fd = yield file_uri("http://ltd.123/", \stream_context_create());
        $this->assertFalse($fd);
        $size = file_meta($fd, 'size');
        $this->assertEquals(0, $size);
        $status = file_meta($fd, 'status');
        $this->assertEquals(400, $status);
        $meta = file_meta($fd);
        $this->assertFalse($meta);
        $bool = yield file_close($fd);
        $this->assertFalse($bool);

        yield \shutdown();
    }

    public function testFileGetSize()
    {
        \coroutine_run($this->taskFileGetSize());
    }

    public function getStatuses($websites)
    {
        $statuses = ['200' => 0, '400' => 0];
        foreach ($websites as $website) {
            $tasks[] = yield \away($this->getWebsiteStatus($website));
        }

        $taskStatus = yield \gather_wait($tasks, 2);
        $this->assertEquals(2, \count($taskStatus));
        \array_map(function ($status) use (&$statuses) {
            if ($status == 200)
                $statuses[$status]++;
            elseif ($status == 400)
                $statuses[$status]++;
        }, $taskStatus);
        return \json_encode($statuses);
    }

    public function getWebsiteStatus($url)
    {
        $fd = yield file_uri($url);
        $this->assertTrue(\is_resource($fd), $url);
        $status = file_meta($fd, 'status');
        $this->assertEquals(200, $status);
        $bool = yield file_close($fd);
        $this->assertTrue($bool);
        return yield $status;
    }

    public function taskFileLines()
    {
        $websites = yield file_file(__DIR__ . \DS . 'list.txt');
        $this->assertCount(5, $websites);
        if ($websites !== false) {
            $this->expectOutputString('{"200":2,"400":0}');
            $data = yield from $this->getStatuses($websites);
            print $data;
        }

        yield \shutdown();
    }

    public function testFileOpenLineUri()
    {
        \coroutine_run($this->taskFileLines());
    }

    public function taskMonitor()
    {
        $watchTask = yield monitor_task(function (?string $filename, int $events, int $status) {
            if ($status == 0) {
                //if ($events & \UV::RENAME)
                //    $this->assertTrue(\is_type($filename, 'string'));
                //if ($events & \UV::CHANGE)
                //    $this->assertEmpty($filename);
            } elseif ($status < 0) {
                $tid = yield \get_task();
                $handle = \coroutine_instance()->taskInstance($tid)->getCustomData();
                $this->assertInstanceOf(\UVFsEvent::class, $handle);
                yield \kill_task();
            }
        });

        yield monitor_dir('watching/temp', $watchTask);

        yield \away(function () {
            yield \sleep_for(0.2);
            yield file_put("watching/temp/new.txt", 'here');
            yield \sleep_for(0.2);
            yield file_unlink("watching/temp/new.txt");
            yield \sleep_for(0.2);
            yield file_delete('watching');
        });

        yield \gather_wait([$watchTask], 0, false);

        yield file_delete('watching');
        yield \shutdown();
    }

    public function testMonitor()
    {
        if (\IS_LINUX)
            $this->markTestSkipped('For Windows.');

        if (!\function_exists('uv_loop_new'))
            $this->markTestSkipped('Test skipped "uv_loop_new" missing.');

        \coroutine_run($this->taskMonitor());
    }

    public function taskMonitorDir()
    {
        $watchTask = yield monitor_task(function (?string $filename, int $events, int $status) {
            if ($status == 0) {
                //if ($events & \UV::RENAME)
                //    $this->assertTrue(\is_type($filename, 'string'));
                //if ($events & \UV::CHANGE)
                //    $this->assertEmpty($filename);
            } elseif ($status < 0) {
                yield \kill_task();
            }
        });
        $this->assertTrue(\is_type($watchTask, 'int'));

        $bool = yield monitor_dir('watching/temp', $watchTask);
        $this->assertTrue($bool);

        yield \away(function () {
            yield \sleep_for(0.2);
            yield file_put("watching/temp/new.txt", 'here');
            yield \sleep_for(0.2);
            yield file_delete('watching');
        });

        $result = yield \gather_wait([$watchTask], 0, false);
        $this->assertNull($result[$watchTask]);

        yield file_delete('watching');
        yield \shutdown();
    }

    public function testMonitorDir()
    {
        if (\IS_LINUX)
            $this->markTestSkipped('For Windows.');

        if (!\function_exists('uv_loop_new'))
            $this->markTestSkipped('Test skipped "uv_loop_new" missing.');

        \coroutine_run($this->taskMonitorDir());
    }

    public function taskMonitorDirLinux()
    {
        $that = &$this;
        $watchTask = yield monitor_task(function (?string $filename, int $events, int $status) use (&$that) {
            if ($status == 0) {
                if ($events & \UV::RENAME)
                    $that->monitorData['RENAME'][] = [$filename, $events];
                elseif ($events & \UV::CHANGE)
                    $that->monitorData['CHANGE'][] =  [$filename, $events];
            } elseif ($status < 0) {
                yield \kill_task();
            }
        });
        $this->assertTrue(\is_type($watchTask, 'int'));

        $bool = yield monitor_dir('watching/temp', $watchTask);
        $this->assertTrue($bool);

        yield file_touch("watching/temp/new.txt");

        $wait = yield \away(function () {
            yield file_touch("watching/temp/new.txt");
        });

        yield;
        yield;
        $this->assertEquals([
            'CHANGE' =>
            [
                0 => [
                    0 => 'new.txt',
                    1 => 2
                ]
            ],
            'RENAME' =>
            [
                0 => [
                    0 => 'new.txt',
                    1 => 1
                ]
            ],
        ], $that->monitorData);

        $bool = yield file_delete('watching');
        $this->assertTrue($bool);

        yield \shutdown();
    }

    public function testMonitorLinux()
    {
        if (\IS_WINDOWS)
            $this->markTestSkipped('For Linux.');

        if (!\function_exists('uv_loop_new'))
            $this->markTestSkipped('Test skipped "uv_loop_new" missing.');

        \coroutine_run($this->taskMonitorDirLinux());
    }
}
