<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use Async\Coroutine\FileStream;
use Async\Coroutine\FileStreamInterface;
use PHPUnit\Framework\TestCase;

class FileStreamTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function get_statuses_again($websites)
    {
        $statuses = ['200' => 0, '400' => 0];
        foreach($websites as $website) {
            $tasks[] = yield \await([$this, 'get_website_status_again'], $website);
        }

        $taskStatus = yield \gather($tasks);
        $this->assertEquals(2, \count($taskStatus));
        foreach($taskStatus as  $id => $status) {
            if (!$status)
                $statuses[$status] = 0;
            else {
                $statuses[$status] += 1;
            }
        }

        return json_encode($statuses);
    }

    public function get_website_status_again($url)
    {
        $object = yield \file_open($url);
        $this->assertTrue($object instanceof FileStreamInterface);
        $status = \file_status($object);
        $this->assertEquals(200, $status);
        $meta = \file_meta($object);
        $this->assertNotNull($meta);
        \file_close($object);
        return $status;
    }

    public function taskFileOpen_Again()
    {
        chdir(__DIR__);
        $instance = yield \file_open('.'.\DS.'list.txt');
        $this->assertTrue($instance instanceof FileStreamInterface);
        $websites = yield \file_lines($instance );
        $this->assertEquals(2, \count($websites));
        $this->assertTrue(\file_valid($instance));
        $this->assertTrue(\is_resource(\file_handle($instance)));
        \file_close($instance);
        $this->assertFalse(\is_resource(\file_handle($instance)));
        if ($websites !== false) {
            $data = yield from $this->get_statuses_again($websites);
            $this->expectOutputString('{"200":2,"400":0}');
            print $data;
        }
    }

    public function taskFileOpen_Get_File()
    {
        $contents = yield \get_file('.'.\DS.'list.txt');
        $this->assertTrue(\is_type($contents, 'bool'));
        chdir(__DIR__);
        $contents = yield \get_file('.'.\DS.'list.txt');
        $this->assertEquals('string', \is_type($contents));
    }

    public function testFileOpen_Again()
    {
        \coroutine_run($this->taskFileOpen_Again());
    }

    public function testFileOpen_Get_File()
    {
        \coroutine_run($this->taskFileOpen_Get_File());
    }
}
