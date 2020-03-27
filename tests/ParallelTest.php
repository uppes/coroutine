<?php

namespace Async\Tests;

use InvalidArgumentException;
use Async\Coroutine\Coroutine;
use Async\Tests\MyClass;
use Async\Tests\InvokableClass;
use Async\Tests\NonInvokableClass;
use PHPUnit\Framework\TestCase;

class ParallelTest extends TestCase
{
	protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function testIt_can_create_and_return_results()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $counter = 0;

        $parallel->add(function () {
                return 2;
            })->then(function (int $output) use (&$counter) {
                $counter += $output + 4;
            });

        $parallel->wait();

        $result = $parallel->results();

        $this->assertEquals(6, $counter, (string) $parallel->status());
        $this->assertEquals(2, $result[0], (string) $parallel->status());
    }

    public function testIt_can_retry()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $counter = 0;

        $parallel->add(function () {
                return 20;
            })->then(function (int $output) use (&$counter) {
                $counter += $output + 2;
            });

        $parallel->wait();

        $result = $parallel->results();

        $this->assertEquals(22, $counter, (string) $parallel->status());
        $this->assertEquals(20, $result[0], (string) $parallel->status());

        $counter = 100;

        $parallel->retry()->then(function (int $output) use (&$counter) {
            $counter += $output + 8;
        });

        $parallel->wait();

        $result = $parallel->results();

        $this->assertEquals(128, $counter, (string) $parallel->status());
        $this->assertEquals(20, $result[0], (string) $parallel->status());
    }

    public function testIt_can_handle_success()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $counter = 0;

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                return 2;
            })->then(function (int $output) use (&$counter) {
                $counter += $output;
            });
        }

        $parallel->wait();

        $this->assertEquals(10, $counter, (string) $parallel->status());
    }

    public function testIt_can_handle_timeout()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $counter = 0;

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                sleep(1000);
            }, 1)->timeout(function () use (&$counter) {
                $counter += 1;
            });
        }

        $parallel->wait();

        $this->assertEquals(5, $counter, (string) $parallel->status());
    }

    public function testIt_can_handle_a_maximum_of_concurrent_processes()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $parallel->concurrency(2);

        $startTime = microtime(true);

        foreach (range(1, 3) as $i) {
            $parallel->add(function () {
                sleep(2);
            });
        }

        $parallel->wait();

        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        $this->assertGreaterThanOrEqual(2, $executionTime, "Execution time was {$executionTime}, expected more than 2.\n".(string) $parallel->status());
        $this->assertCount(3, $parallel->getFinished(), (string) $parallel->status());
    }

    public function testIt_can_handle_sleep_array_access()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $counter = 0;

        $parallel->sleepTime(10000);

        foreach (range(1, 5) as $i) {
            $parallel[] = $parallel->add(function () {
                usleep(random_int(100, 1000));

                return 2;
            });
        }

        $parallel->wait();

        $this->assertTrue(isset($parallel[0]));

        $array = $parallel[0];

        $this->assertEquals(2, $array->getOutput());

        unset($parallel[0]);

        $this->assertFalse(isset($parallel[0]));
    }

    public function testIt_returns_all_the_output_as_an_array()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $result = null;

        foreach (range(1, 5) as $i) {
            $parallel[] = $parallel->add(function () {
                return 2;
            });
        }

        $result = $parallel->wait();

        $this->assertCount(5, $result);
        $this->assertEquals(10, array_sum($result), (string) $parallel->status());
    }

    public function testIt_can_use_a_class_from_the_parent_process()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        /** @var MyClass $result */
        $result = null;

        $parallel[] = $parallel->add(function () {
            $class = new MyClass();

            $class->property = true;

            return $class;
        })->then(function (MyClass $class) use (&$result) {
            $result = $class;
        });

        $parallel->wait();

        $this->assertInstanceOf(MyClass::class, $result);
        $this->assertTrue($result->property);
    }

    public function testIt_can_run_invokable_classes()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();


        $parallel->add(new InvokableClass());

        $results = $parallel->wait();

        $this->assertEquals(2, $results[0]);
    }

    public function testIt_reports_error_for_non_invokable_classes()
    {
        $this->expectException(\InvalidArgumentException::class);

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $parallel->add(new NonInvokableClass());
        $results = $coroutine->run();
    }

    public function testIt_can_check_for_asynchronous_support_speed()
    {
        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $stopwatch = \microtime(true);

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                usleep(1000);
            });
        }

        $parallel->wait();

        $stopwatchResult = \microtime(true) - $stopwatch;

        if (\IS_LINUX) {
            $expect = (float) 0.4;
            $this->assertTrue($parallel->isPcntl());
        } else {
            $expect = (float) 0.5;
            $this->assertFalse($parallel->isPcntl());
        }

        $this->assertLessThan($expect, $stopwatchResult, "Execution time was {$stopwatchResult}, expected less than {$expect}.\n" . (string) $parallel->status());
    }
}
