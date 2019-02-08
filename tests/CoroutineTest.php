<?php

namespace Async\Tests;

use Async\Coroutine\Call;
use Async\Coroutine\Scheduler;
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

    public function testScheduler() 
    {
        $scheduler = new Scheduler();
        $this->assertInstanceOf('\Async\Coroutine\SchedulerInterface', $scheduler);

        $taskId = $scheduler->coroutine($this->task1());
        $this->assertNotNull($taskId);
        
        $scheduler->coroutine($this->task2());
        $scheduler->coroutine($this->task3());

        $scheduler->run();
        
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
        $tid = (yield Call::taskId()); // <-- here's the syscall!
        for ($i = 1; $i <= $max; ++$i) {
            $this->task .= "This is task $tid iteration $i.\n";
            yield;
        }
    }

    public function testSysCall_GetTaskId() 
    {
        $this->task = null;

        $scheduler = new Scheduler();

        $scheduler->coroutine($this->task(10));
        $scheduler->coroutine($this->task(5));
        $scheduler->coroutine($this->task(3));
        
        $scheduler->run();

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
        while (true) {
            $this->task .= "Child task $tid still alive!\n";
            yield;
        }
    }

    public function taskCall() 
    {
        $tid = (yield Call::taskId());
        $childTid = (yield Call::coroutine($this->childTask()));

        for ($i = 1; $i <= 6; ++$i) {            
            $this->task .= "Parent task $tid iteration $i.\n";
            yield;
    
            if ($i == 3) yield Call::killTask($childTid);
        }
    }

    public function testCall() 
    {
        $this->task = null;

        $scheduler = new Scheduler();
        $scheduler->coroutine($this->taskCall());
        $scheduler->run();

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

        $this->assertEquals(3, preg_match_all('/Child task 3 still alive!/', $this->task, $matches));
    }
}
