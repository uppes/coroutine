<?php

namespace Async\Tests;

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
        $fd = yield \file_open(FIXTURE_PATH, 'r');
        $this->assertEquals('resource', \is_type($fd));

        $data = yield \file_read($fd, 0, 32);
        $this->assertEquals('string', \is_type($data));

        $this->assertEquals('Hello', \rtrim($data));
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $bool = yield \file_close($fd);
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
        $fd = yield \file_open(FIXTURE_PATH, 'r');
        $this->assertEquals('resource', \is_type($fd));

        $size = yield \file_fstat($fd, 'size');
        $this->assertEquals('int', \is_type($size));
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $data = yield \file_read($fd, 1, $size);
        $this->assertEquals('string', \is_type($data));

        $this->assertEquals('ello', \rtrim($data));
        $this->assertGreaterThanOrEqual(4, $this->counterResult);

        $bool = yield \file_close($fd);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(5, $this->counterResult);
        yield \shutdown();
    }

    public function testOpenReadOffsetFstat()
    {
        \coroutine_run($this->taskOpenReadOffsetFstat());
    }

    public function taskWrite()
    {
        yield \away($this->counterTask());
        $fd = yield \file_open("./tmp", 'a');
        $this->assertEquals('resource', \is_type($fd));

        $data = yield \file_write($fd, "hello");
        $this->assertEquals('int', \is_type($data));

        $this->assertEquals(5, $data);
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $fd = yield \file_fdatasync($fd);
        $this->assertEquals('resource', \is_type($fd));
        $this->assertGreaterThanOrEqual(8, $this->counterResult);

        $bool = yield \file_close($fd);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(9, $this->counterResult);

        $size = yield \file_size("./tmp");
        $this->assertEquals('int', \is_type($size));
        $this->assertGreaterThanOrEqual(10, $this->counterResult);

        $bool = yield \file_rename("./tmp", "./tmpNew");
        $this->assertTrue($bool);

        $bool = yield \file_touch('./tmpNew');
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(12, $this->counterResult);

        $bool = yield \file_unlink("./tmpNew");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(13, $this->counterResult);

        $bool = yield \file_mkdir(DIRECTORY_PATH);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(14, $this->counterResult);

        $bool = yield \file_rmdir(DIRECTORY_PATH);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(15, $this->counterResult);

        $fd = yield \file_open("tmp", 'bad');
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

        $count = yield \file_put($new, $contents1);
        $this->assertEquals(8, $count);

        $contents2 = yield \file_get($new);

        $this->assertSame($contents1, $contents2);

        yield \file_unlink($new);
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

        $bool = yield \file_symlink($original, $link);
        $this->assertTrue($bool);

        $array = yield \file_lstat($link);
        $this->assertIsArray($array);

        $result = yield \file_readlink($link);
        $this->assertSame($original, $result);

        yield \file_unlink($link);
        yield \shutdown();
    }

    public function testFileLink()
    {
        \coroutine_run($this->taskFileLink());
    }

    public function taskFileContents()
    {
        yield \away($this->counterTask());

        $data = yield \file_contents(null);
        $this->assertFalse($data);

        $text = \str_repeat('abcde', 256);
        $fd = yield \file_open('php://temp', 'w+');
        $written = yield \file_write($fd, $text);
        yield \file_fdatasync($fd);
        $this->assertEquals(\strlen($text), $written);

        $data = yield \file_contents($fd);
        $this->assertEquals($text, $data);

        $this->assertGreaterThanOrEqual(10, $this->counterResult);

        $moreData = yield \file_contents($fd);
        $this->assertEquals('', $moreData);

        $this->assertGreaterThanOrEqual(11, $this->counterResult);

        $bool = yield \file_close($fd);
        $this->assertTrue($bool);

        yield \shutdown();
    }

    public function testFileContents()
    {
        \coroutine_run($this->taskFileContents());
    }

    public function taskFileSystem()
    {
        \file_operation();
        yield \away($this->counterTask());
        $bool = yield \file_touch('./tmpTouch');
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(5, $this->counterResult);

        $size = yield \file_size("./tmpTouch");
        $this->assertEquals(0, $size);
        $this->assertGreaterThanOrEqual(7, $this->counterResult);

        $bool = yield \file_exist("./tmpTouch");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(10, $this->counterResult);

        $bool = yield \file_rename("./tmpTouch", "./tmpRename");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(13, $this->counterResult);

        $bool = yield \file_unlink("./tmpRename");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(16, $this->counterResult);

        $bool = yield \file_mkdir(DIRECTORY_PATH);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(19, $this->counterResult);

        $bool = yield \file_rmdir(DIRECTORY_PATH);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(25, $this->counterResult);

        \file_operation(true);
        $bool = yield \file_touch("./tmpNew");
        $this->assertTrue($bool);
        $result = yield FileSystem::utime("./tmpNew");
        $this->assertTrue($result);
        $bool = yield \file_unlink("./tmpNew");
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
        $array = yield \file_scandir('.');
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
        $fd = yield \file_open(FIXTURE_PATH, 'r');
        $size = yield \file_fstat($fd, 'size');
        $outFd = yield \file_open('php://temp', 'w+');
        $written = yield \file_sendfile($outFd, $fd, 0, $size);

        $this->assertEquals($size, $written);
        $data = yield \file_contents($outFd);
        $this->assertEquals('Hello', \trim($data));
        $this->assertGreaterThanOrEqual(6, $this->counterResult);
        yield \file_close($fd);
        yield \file_close($outFd);

        yield \shutdown();
    }

    public function testFileSystemSendfile()
    {
        \coroutine_run($this->taskFileSystemSendfile());
    }

    public function taskFileSendfile()
    {
        \file_operation();
        yield \away($this->counterTask());
        $fd = yield \file_open(FIXTURE_PATH, 'r');
        $size = yield \file_fstat($fd, 'size');
        $outFd = yield \file_open('php://temp', 'w+');
        $written = yield \file_sendfile($outFd, $fd, 0, $size);
        $this->assertEquals($size, $written);
        $data = yield \file_contents($outFd);
        $this->assertEquals('Hello', \trim($data));
        $this->assertGreaterThanOrEqual(8, $this->counterResult);
        yield \file_close($fd);
        yield \file_close($outFd);

        yield \shutdown();
    }

    public function testFileSendfile()
    {
        \coroutine_run($this->taskFileSendfile());
    }

    public function taskSystemScandir()
    {
        \file_operation();
        yield \away($this->counterTask());
        $array = yield \file_scandir('.');
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
        yield \spawn_system('/');

        yield \shutdown();
    }

    public function testSystemError()
    {
        \coroutine_run($this->taskSystemError());
    }

    public function taskFileGet()
    {
        $contents = yield \file_get('.' . \DS . 'list.txt');
        $this->assertTrue(\is_type($contents, 'bool'));
        $contents = yield \file_get(__DIR__ . \DS . 'list.txt');
        $this->assertEquals('string', \is_type($contents));
    }

    public function testFileGet()
    {
        \coroutine_run($this->taskFileGet());
    }


    public function taskFileGetSize()
    {
        $contents = yield \file_get("https://google.com/");
        $this->assertEquals('string', \is_type($contents));
        $this->assertGreaterThanOrEqual(500, \strlen($contents));
        $fd = yield \file_uri("https://nytimes.com/");
        $this->assertTrue(\is_resource($fd));
        $size = \file_meta($fd, 'size');
        $this->assertGreaterThanOrEqual(500, $size);
        $bool = yield \file_close($fd);
        $this->assertTrue($bool);
        $fd = yield \file_uri("http://ltd.123/", \stream_context_create());
        $this->assertFalse($fd);
        $size = \file_meta($fd, 'size');
        $this->assertEquals(0, $size);
        $status = \file_meta($fd, 'status');
        $this->assertEquals(400, $status);
        $meta = \file_meta($fd);
        $this->assertFalse($meta);
        $bool = yield \file_close($fd);
        $this->assertFalse($bool);
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
        $fd = yield \file_uri($url);
        $this->assertTrue(\is_resource($fd));
        $status = \file_meta($fd, 'status');
        $this->assertEquals(200, $status);
        $bool = yield \file_close($fd);
        $this->assertTrue($bool);
        return yield $status;
    }

    public function taskFileLines()
    {
        $websites = yield \file_file(__DIR__ . \DS . 'list.txt');
        $this->assertCount(5, $websites);
        if ($websites !== false) {
            $data = yield from $this->getStatuses($websites);
            $this->expectOutputString('{"200":2,"400":0}');
            print $data;
        }
    }

    public function testFileOpenLineUri()
    {
        \coroutine_run($this->taskFileLines());
    }
}
