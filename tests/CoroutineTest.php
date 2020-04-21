<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
use Async\Coroutine\Coroutine;
use PHPUnit\Framework\TestCase;

class CoroutineTest extends TestCase
{
    protected $task = '';

	protected function setUp(): void
    {
        \coroutine_clear();
    }

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

    public function testCoroutine()
    {
        $coroutine = new Coroutine();
        $this->assertInstanceOf('\Async\Coroutine\Coroutine', $coroutine);

        $taskId = $coroutine->createTask($this->task1());
        $this->assertNotNull($taskId);

        $coroutine->createTask($this->task2());
        $coroutine->createTask($this->task3());

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
        $tid = (yield Kernel::getTask()); // <-- here's the system call!
        for ($i = 1; $i <= $max; ++$i) {
            $this->task .= "This is task $tid iteration $i.\n";
            yield;
        }
    }

    public function testKernel_TaskId()
    {
        $this->task = '';

        $coroutine = new Coroutine();

        $coroutine->createTask($this->task(10));
        $coroutine->createTask($this->task(5));
        $coroutine->createTask($this->task(3));

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
        $tid = (yield Kernel::getTask());
        $this->assertNotNull($tid);
        while (true) {
            $this->task .= "Child task $tid still alive!\n";
            yield;
        }
    }

    public function taskKernel()
    {
        $tid = (yield Kernel::getTask());
        $childTid = (yield Kernel::createTask($this->childTask()));

        for ($i = 1; $i <= 6; ++$i) {
            $this->task .= "Parent task $tid iteration $i.\n";
            yield;

            if ($i == 3) yield Kernel::cancelTask($childTid);
        }
    }

    public function testKernel()
    {
        $this->task = '';

        $coroutine = new Coroutine();
        $coroutine->createTask($this->taskKernel());
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

        $coroutine->shutdown();
    }

    function testAddWriteStream()
	{
        $coroutine = new Coroutine();
        $h = fopen('php://temp', 'r+');
        $coroutine->addWriter($h, function() use ($h, $coroutine) {
            fwrite($h, 'hello world');
            $coroutine->removeWriter($h);
        });
        $coroutine->run();
        rewind($h);
        $this->assertEquals('hello world', stream_get_contents($h));

        $coroutine->shutdown();
    }

    function testAddReadStream()
	{
        $coroutine = new Coroutine();
        $h = fopen('php://temp', 'r+');
        fwrite($h, 'hello world');
        rewind($h);
        $result = null;
        $coroutine->addReader($h, function() use ($h, $coroutine, &$result) {
            $result = fgets($h);
            $coroutine->removeReader($h);
        });
        $coroutine->run();
        $this->assertEquals('hello world', $result);

        $coroutine->shutdown();
    }

    function testTimeout()
	{
        $coroutine = new Coroutine();
        $check  = 0;
        $coroutine->addTimeout(function() use (&$check) {
            $check++;
        }, 0.02);
        $coroutine->run();
        $this->assertEquals(1, $check);

        $coroutine->shutdown();
    }

    function testTimeoutOrder()
	{
        $coroutine = new Coroutine();
        $check  = [];
        $coroutine->addTimeout(function() use (&$check) {
            $check[] = 'a';
        }, 0.2);
        $coroutine->addTimeout(function() use (&$check) {
            $check[] = 'b';
        }, 0.1);
        $coroutine->addTimeout(function() use (&$check) {
            $check[] = 'c';
        }, 0.3);
        $coroutine->run();
        $this->assertEquals(['b', 'a', 'c'], $check);

        $coroutine->shutdown();
    }

    function testTimeoutOrderNative()
	{
        $coroutine = new Coroutine();
        $coroutine->setup(false);

        $check  = [];
        $coroutine->addTimeout(function() use (&$check) {
            $check[] = 'a';
        }, 0.2);

        $coroutine->addTimeout(function() use (&$check) {
            $check[] = 'b';
        }, 0.1);

        $coroutine->addTimeout(function() use (&$check) {
            $check[] = 'c';
        }, 0.3);

        $coroutine->run();
        $this->assertEquals(['b', 'a', 'c'], $check);

        $coroutine->shutdown();
    }
}
