<?php

namespace Async\Tests;

use parallel\Runtime;
use PHPUnit\Framework\TestCase;

class RunTimeParallelTest extends TestCase
{
    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function testShowing_future_as_return_value()
    {
        $runtime = new Runtime;
        $future  = $runtime->run(function () {
            return "World";
        });

        $this->expectOutputString('World');
        $this->assertEquals('World', $future->value());
    }

    public function testShowing_future_as_synchronization_point()
    {
        $runtime = new Runtime;
        $future  = $runtime->run(function () {
            echo "in child ";
            for ($i = 0; $i < 500; $i++) {
                if ($i % 10 == 0) {
                    echo ".";
                }
            }

            echo " leaving child";
        });

        $future->value();
        $this->expectOutputRegex('/[..... leaving child]/');
    }
}
