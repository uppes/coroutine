<?php

namespace Async\Tests;

use PHPUnit\Framework\TestCase;

class DeferTest extends TestCase
{
    protected $task = null;

	protected function setUp(): void
    {
        \coroutine_clear();
    }

    function taskDelay($a)
    {
        echo "in defer 3-{$a}\n";
    }

    function task() {
        echo "before defer\n";
        \defer($task, [$this, "taskDelay"], 1);
        \defer($task, [$this, "taskDelay"], 2);
        \defer($task, [$this, "taskDelay"], 3);
        echo "after defer\n";
    }

    public function testDefer()
    {
        $this->expectOutputString("start\nbefore defer\nafter defer\nin defer 3-3\nin defer 3-2\nin defer 3-1\nend\n");
        echo "start\n";
        $this->task();
        echo "end\n";
    }

    public function testDeferReturn()
    {
        $this->expectOutputString("0\n3210\n2-3\n");

        function variableReference()
        {
            $i = 0;
            defer($e, 'printf', $i);
            $i++;
        }
        variableReference();echo "\n";

        function variableReferenceII()
        {
            for($i = 0; $i < 4; $i++){
                defer($a, 'printf', $i);
            }
        }
        variableReferenceII();echo "\n";

        function variableReferenceReturn()
        {
            $i = 1;
            $o = new \stdClass();
            $o->i = 2;
            defer($e, function () use (&$i, $o) {
                $o->i++;
                $i++;
            });

            $i++;
            return [$i, $o];
        }

        list($i, $o) = variableReferenceReturn();
        echo "{$i}-{$o->i}\n";
	}

    function error($a) {
        \recover($task, [$this, 'taskPanic'], $a);
        if ($a == 2) {
            print("Panicking!\n");
            \panic();
        }
    }
    function taskFoo($a) {
        print "in defer 3-{$a}\n";
        $this->error($a);
    }
    function taskPanic($a = 0) {
        print "Panic Catcher!\n";
        print("Recovered in taskFoo($a)\n");
    }
    function taskStart() {
        print "before defer\n";
        \defer($task, [$this, "taskFoo"], 1);
        \defer($task, [$this, "taskFoo"], 2);
        \defer($task, [$this, "taskFoo"], 3);
        print "after defer\n";
    }

    public function testDeferRecover()
    {
        $this->expectOutputString("start\nbefore defer\nafter defer\nin defer 3-3\nin defer 3-2\nPanicking!\nPanic Catcher!\nRecovered in taskFoo(2)\nin defer 3-1\nend\n");

        print "start\n";
        $this->taskStart();
        print "end\n";
	}
}
