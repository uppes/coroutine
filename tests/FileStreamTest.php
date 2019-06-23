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

    public function get_statuses($websites) 
    {
        $statuses = ['200' => 0, '400' => 0];
        foreach($websites as $website) {
            $tasks[] = yield \await([$this, 'get_website_status'], $website);
        }
        
        $taskStatus = yield \gather($tasks);
        $this->assertEquals(2, \count($taskStatus));
        foreach($taskStatus as  $id => $status) {
            if (!$status)
                $statuses[$status] = 0;
            else {
                $statuses[$status] += 1;
                $this->assertEquals(200, $status);
            }
        }
        
        return json_encode($statuses);
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
    
    public function get_website_status($url) 
    {
        $response = yield \head_uri();
        $this->assertFalse($response);
        $response = yield \head_uri($url);
        \clear_uri();
        $this->assertEquals(3, \count($response));
        [$meta, $status, $retry] = $response;
        $this->assertEquals('array', \is_type($meta));
        $this->assertEquals('bool', \is_type($retry));
        $this->assertEquals(200, $status);
        return $status;
    }

    public function get_website_status_again($url) 
    {
        $object = yield \file_open(null, $url);
        $this->assertTrue($object instanceof FileStreamInterface);
        $status = \file_status($object);
        $this->assertEquals(200, $status);
        $meta = \file_meta($object);
        $this->assertNotNull($meta);
        \file_close($object);
        return $status;
    }
    
    public function taskFileOpen() 
    {
        chdir(__DIR__);
        $instance = yield Kernel::fileOpen(null, '.'.\DS.'list.txt');
        $this->assertTrue($instance instanceof FileStreamInterface);
        $websites = yield $instance->fileLines();
        $this->assertEquals(2, \count($websites));
        $this->assertTrue($instance->fileValid());
        $this->assertTrue(\is_resource($instance->fileHandle()));
        $instance->fileClose();
        $this->assertFalse(\is_resource($instance->fileHandle()));
        if ($websites !== false) {
            $data = yield from $this->get_statuses($websites);
            $this->expectOutputString('{"200":2,"400":0}');
            print $data;
        }
    }

    public function taskFileOpen_Again() 
    {
        chdir(__DIR__);
        $instance = yield \file_open(null, '.'.\DS.'list.txt');
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

    /**
     * @covers Async\Coroutine\Kernel::createTask
     * @covers Async\Coroutine\Kernel::fileOpen
     * @covers Async\Coroutine\Kernel::readWait
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::addReader
     * @covers Async\Coroutine\Coroutine::ioStreams
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\FileStream::fileOpen
     * @covers Async\Coroutine\FileStream::fileClose
     * @covers Async\Coroutine\FileStream::fileContents
     * @covers Async\Coroutine\FileStream::fileValid
     * @covers Async\Coroutine\FileStream::fileHandle
     * @covers Async\Coroutine\FileStream::fileLines
     * @covers Async\Coroutine\HttpRequest::head
     * @covers Async\Coroutine\HttpRequest::request
     * @covers Async\Coroutine\FileStream::getMeta
     * @covers Async\Coroutine\Task::result
     * @covers Async\Coroutine\Task::rescheduled
     * @covers Async\Coroutine\Task::clearResult
     * @covers Async\Coroutine\Task::completed
     * @covers Async\Coroutine\Task::pending
     * @covers \gather
     * @covers \head_uri
     * @covers \create_uri
     * @covers \clear_uri
     * @covers \file_close
     * @covers \file_meta
     * @covers \file_open
     * @covers \file_valid
     * @covers \file_handle
     * @covers \file_status
     * @covers \file_lines
     * @covers \file_Contents
     * @covers \get_file
     * @covers \is_type
     */
    public function testFileOpen() 
    {
        \coroutine_run($this->taskFileOpen());
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
