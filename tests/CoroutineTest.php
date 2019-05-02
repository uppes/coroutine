<?php

namespace Async\Tests;

use Async\Coroutine\Call;
use Async\Coroutine\Coroutine;
use PHPUnit\Framework\TestCase;

class CoroutineTest extends TestCase 
{
    protected $task = null;

    public function task1() 
    {
        for ($i = 1; $i <= 10; ++$i) {
            $this->task .= "This is task 1 iteration $i.\n";
            yield;
        }
    }

    public function task2() 
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->task .= "This is task 2 iteration $i.\n";
            yield;
        }
    }

    public function task3() 
    {
        for ($i = 1; $i <= 3; ++$i) {
            $this->task .= "This is task 3 iteration $i.\n";
            yield;
        }
    }

    /**
     * @covers Async\Coroutine\Coroutine::addTask
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioSocketPoll
     * @covers Async\Coroutine\Coroutine::runCoroutines
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\Task::taskId
     * @covers Async\Coroutine\Task::run
     */
    public function testCoroutine() 
    {
        $coroutine = new Coroutine();
        $this->assertInstanceOf('\Async\Coroutine\Coroutine', $coroutine);

        $taskId = $coroutine->addTask($this->task1());
        $this->assertNotNull($taskId);
        
        $coroutine->addTask($this->task2());
        $coroutine->addTask($this->task3());

        $coroutine->run();
        
        $expect[] = "This is task 1 iteration 1.";
        $expect[] = "This is task 2 iteration 1.";
        $expect[] = "This is task 3 iteration 1.";
        $expect[] = "This is task 1 iteration 2.";
        $expect[] = "This is task 2 iteration 2.";
        $expect[] = "This is task 3 iteration 2.";
        $expect[] = "This is task 1 iteration 3.";
        $expect[] = "This is task 2 iteration 3.";
        $expect[] = "This is task 3 iteration 3.";
        $expect[] = "This is task 1 iteration 4.";
        $expect[] = "This is task 2 iteration 4.";
        $expect[] = "This is task 1 iteration 5.";
        $expect[] = "This is task 2 iteration 5.";
        $expect[] = "This is task 1 iteration 6.";
        $expect[] = "This is task 1 iteration 7.";
        $expect[] = "This is task 1 iteration 8.";
        $expect[] = "This is task 1 iteration 9.";
        $expect[] = "This is task 1 iteration 10.";

        foreach ($expect as $iteration)
            $this->assertStringContainsString($iteration, $this->task);
    }

    public function task($max) 
    {
        $tid = (yield Call::taskId()); // <-- here's the system call!
        for ($i = 1; $i <= $max; ++$i) {
            $this->task .= "This is task $tid iteration $i.\n";
            yield;
        }
    }

    /**
     * @covers Async\Coroutine\Coroutine::addTask
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::ioSocketPoll
     * @covers Async\Coroutine\Coroutine::runCoroutines
     * @covers Async\Coroutine\Coroutine::run
     */
    public function testCall_TaskId() 
    {
        $this->task = null;

        $coroutine = new Coroutine();

        $coroutine->addTask($this->task(10));
        $coroutine->addTask($this->task(5));
        $coroutine->addTask($this->task(3));
        
        $coroutine->run();

        $expect[] = "This is task 1 iteration 1.";
        $expect[] = "This is task 2 iteration 1.";
        $expect[] = "This is task 3 iteration 1.";
        $expect[] = "This is task 1 iteration 2.";
        $expect[] = "This is task 2 iteration 2.";
        $expect[] = "This is task 3 iteration 2.";
        $expect[] = "This is task 1 iteration 3.";
        $expect[] = "This is task 2 iteration 3.";
        $expect[] = "This is task 3 iteration 3.";
        $expect[] = "This is task 1 iteration 4.";
        $expect[] = "This is task 2 iteration 4.";
        $expect[] = "This is task 1 iteration 5.";
        $expect[] = "This is task 2 iteration 5.";
        $expect[] = "This is task 1 iteration 6.";
        $expect[] = "This is task 1 iteration 7.";
        $expect[] = "This is task 1 iteration 8.";
        $expect[] = "This is task 1 iteration 9.";
        $expect[] = "This is task 1 iteration 10.";

        foreach ($expect as $iteration)
            $this->assertStringContainsString($iteration, $this->task);
    }

    public function childTask() 
    {
        $tid = (yield Call::taskId());
        $this->assertNotNull($tid);
        while (true) {
            $this->task .= "Child task $tid still alive!\n";
            yield;
        }
    }

    public function taskCall() 
    {
        $tid = (yield Call::taskId());
        $childTid = (yield Call::addTask($this->childTask()));

        for ($i = 1; $i <= 6; ++$i) {            
            $this->task .= "Parent task $tid iteration $i.\n";
            yield;
    
            if ($i == 3) yield Call::removeTask($childTid);
        }
    }

    /**
     * @covers Async\Coroutine\Coroutine::addTask
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::runStreams
     * @covers Async\Coroutine\Coroutine::runCoroutines
     * @covers Async\Coroutine\Coroutine::run
     */
    public function testCall() 
    {
        $this->task = null;

        $coroutine = new Coroutine();
        $coroutine->addTask($this->taskCall());
        $coroutine->run();

        $expect[] = "Parent task 1 iteration 1.";
        $expect[] = "Child task 3 still alive!";
        $expect[] = "Parent task 1 iteration 2.";
        $expect[] = "Child task 3 still alive!";
        $expect[] = "Parent task 1 iteration 3.";
        $expect[] = "Child task 3 still alive!";
        $expect[] = "Parent task 1 iteration 4.";
        $expect[] = "Parent task 1 iteration 5.";
        $expect[] = "Parent task 1 iteration 6.";

        foreach ($expect as $iteration)
            $this->assertStringContainsString($iteration, $this->task);

        $this->assertEquals(3, preg_match_all('/3 still alive!/', $this->task, $matches));
    }
    
    /**
     * @covers Async\Coroutine\Coroutine::addWriteStream
     * @covers Async\Coroutine\Coroutine::removeWriteStream
     * @covers Async\Coroutine\Coroutine::runStreams
     * @covers Async\Coroutine\Coroutine::ioSocketPoll
     * @covers Async\Coroutine\Coroutine::runCoroutines
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\Task::taskId
     * @covers Async\Coroutine\Task::run
     */
    function testAddWriteStream() 
	{
        $coroutine = new Coroutine();
        $h = fopen('php://temp', 'r+');
        $coroutine->addWriteStream($h, function() use ($h, $coroutine) {
            fwrite($h, 'hello world');
            $coroutine->removeWriteStream($h);
        });
        $coroutine->run();
        rewind($h);
        $this->assertEquals('hello world', stream_get_contents($h));
    }

    /**
     * @covers Async\Coroutine\Coroutine::addReadStream
     * @covers Async\Coroutine\Coroutine::removeReadStream
     * @covers Async\Coroutine\Coroutine::runStreams
     * @covers Async\Coroutine\Coroutine::ioSocketPoll
     * @covers Async\Coroutine\Coroutine::runCoroutines
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\Task::taskId
     * @covers Async\Coroutine\Task::run
     */
    function testAddReadStream() 
	{
        $coroutine = new Coroutine();
        $h = fopen('php://temp', 'r+');
        fwrite($h, 'hello world');
        rewind($h);
        $result = null;
        $coroutine->addReadStream($h, function() use ($h, $coroutine, &$result) {
            $result = fgets($h);
            $coroutine->removeReadStream($h);
        });
        $coroutine->run();
        $this->assertEquals('hello world', $result);
    }
}
