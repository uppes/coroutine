<?php

namespace Async\Tests;

use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    protected $task = null;

	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function childTask()
    {
        $tid = yield \get_task();
        while (true) {
            $this->task .= "Child task $tid still alive!\n";
            yield;
        }
    }

    public function parentTask()
    {
        $tid = yield \get_task();
        $childTid = yield \away([$this, 'childTask']);
        $this->assertEquals('int', \is_type($childTid));

        for ($i = 1; $i <= 6; ++$i) {
            $this->task .= "Parent task $tid iteration $i.\n";
            yield;

            if ($i == 3) {
                $bool = yield \cancel_task($childTid);
                $this->assertTrue(\is_type($bool, 'bool'));
            }
        }
    }

    public function testCoreFunctions()
    {
        $this->task = '';

        \coroutine_instance();
        \coroutine_create(\awaitAble([$this, 'parentTask']));
        \coroutine_run();

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

        $this->assertNotEquals(4, \preg_match_all('/Child task 3/', $this->task, $matches));
        $this->assertEquals(3, \preg_match_all('/Child task 3 still alive!/', $this->task, $matches));
        $this->assertEquals(6, \preg_match_all('/Parent task 1/', $this->task, $matches));
        $this->assertEquals(9, \preg_match_all('/task/', $this->task, $matches));
    }

    public function testCurry()
    {
		$add = \curry(function($x, $y) {
		    return $x + $y;
		});
		$this->assertEquals(3, $add(1, 2));
		$addFive = $add(5); // this is a function
		$this->assertEquals(6, $addFive(1));
		$data = [1, 2, 3, 4, 5];
		$slice = \curry('array_slice');
		$itemsFrom = $slice($data);
		$this->assertEquals([3, 4, 5], $itemsFrom(2));
		$this->assertEquals([2, 3], $itemsFrom(1, 2));
		// Notice that optional arguments are ignored !
		$polynomial = \curry(function($a, $b, $c, $x) {
		    return $a * $x * $x + $b * $x + $c;
		});
		$f = $polynomial(0, 2, 1); // 2 * $x + 1
		$this->assertEquals(11, $f(5));
    }
}
