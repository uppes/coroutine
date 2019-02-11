<?php

namespace Async\Tests;

use Async\Coroutine\Coroutine;
use PHPUnit\Framework\TestCase;

class GlobalTest extends TestCase 
{
    protected $task = null;

    public function childTask() 
    {
        $tid = yield asyncId();
        while (true) {
            $this->task .= "Child task $tid still alive!\n";
            yield;
        }
    }

    public function parentTask() 
    {
        $tid = yield asyncId();
        $childTid = yield from await([$this, 'childTask']);
        
        for ($i = 1; $i <= 6; ++$i) {
            $this->task .= "Parent task $tid iteration $i.\n";
            yield;
        
            if ($i == 3) $this->assertNull(yield asyncRemove($childTid));
        }
    }

    public function testGlobalFunctions() 
    {
        $this->task = null;

        $coroutine = new Coroutine();
        $coroutine->add( awaitAble([$this, 'parentTask']) );
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

        $this->assertNotEquals(4, preg_match_all('/Child task 3/', $this->task, $matches));
        $this->assertEquals(3, preg_match_all('/Child task 3 still alive!/', $this->task, $matches));
        $this->assertEquals(6, preg_match_all('/Parent task 1/', $this->task, $matches));
        $this->assertEquals(9, preg_match_all('/task/', $this->task, $matches));
    }
}
