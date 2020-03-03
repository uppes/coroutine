<?php

namespace Async\Tests;

use Async\Coroutine\FileSystem;
use PHPUnit\Framework\TestCase;

class FileSystemTest extends TestCase
{
    protected $counterResult = null;

    protected function setUp(): void
    {
        \coroutine_clear();
        if (!defined('FIXTURE_PATH'))
            \define("FIXTURE_PATH", dirname(__FILE__) . "/libuv/fixtures/hello.data");
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
        $fd = yield FileSystem::open(FIXTURE_PATH, 'r');
        $this->assertEquals('resource', \is_type($fd));

        $data = yield FileSystem::read($fd, 0, 32);
        $this->assertEquals('string', \is_type($data));

        $this->assertEquals('Hello', \rtrim($data));
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $bool = yield FileSystem::close($fd);
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
        $fd = yield FileSystem::open(FIXTURE_PATH, 'r');
        $this->assertEquals('resource', \is_type($fd));

        $size = yield FileSystem::fstat($fd, 'size');
        $this->assertEquals('int', \is_type($size));
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $data = yield FileSystem::read($fd, 1, $size);
        $this->assertEquals('string', \is_type($data));

        $this->assertEquals('ello', \rtrim($data));
        $this->assertGreaterThanOrEqual(4, $this->counterResult);

        $bool = yield FileSystem::close($fd);
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
        $fd = yield FileSystem::open("./tmp", 'a', \UV::S_IRWXU | \UV::S_IRUSR);
        $this->assertEquals('resource', \is_type($fd));

        $data = yield FileSystem::write($fd, "hello", 0);
        $this->assertEquals('int', \is_type($data));

        $this->assertEquals(5, $data);
        $this->assertGreaterThanOrEqual(3, $this->counterResult);

        $fd = yield FileSystem::fdatasync($fd);
        $this->assertEquals('resource', \is_type($fd));
        $this->assertGreaterThanOrEqual(8, $this->counterResult);

        $bool = yield FileSystem::close($fd);
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(9, $this->counterResult);

        $size = yield \file_size("./tmp");
        $this->assertEquals('int', \is_type($size));
        $this->assertGreaterThanOrEqual(10, $this->counterResult);

        $bool = yield \file_rename("./tmp", "./tmpNew");
        $this->assertTrue($bool);

        $bool = yield \file_touch('./tmpNew');
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(14, $this->counterResult);

        $bool = yield \file_unlink("./tmpNew");
        $this->assertTrue($bool);
        $this->assertGreaterThanOrEqual(15, $this->counterResult);

        $bool = yield \file_touch("./tmpNew");
        $this->assertFalse($bool);
        $this->assertGreaterThanOrEqual(16, $this->counterResult);
        yield \shutdown();
    }

    public function testWrite()
    {
        \coroutine_run($this->taskWrite());
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

        \file_operation(true);
        $bool = yield \file_touch("./tmpNew");
        $this->assertFalse($bool);

        yield \shutdown();
    }

    public function testFileSystem()
    {
        \coroutine_run($this->taskFileSystem());
    }
}
