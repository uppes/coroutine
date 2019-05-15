<?php

namespace Async\Tests;

use InvalidArgumentException;
use Async\Coroutine\Parallel;
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

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::ispcntl
     * @covers Async\Coroutine\Parallel::status
     */
    public function testIt_can_check_for_asynchronous_support_speed()
    {
        $parallel = new Parallel();

        $stopwatch = \microtime(true);

        foreach (range(1, 5) as $i) {
            $parallel->add(function () {
                usleep(1000);
            });
        }

        $parallel->wait();

        $stopwatchResult = \microtime(true) - $stopwatch;
		
		if ('\\' !== \DIRECTORY_SEPARATOR) {
			$expect = (float) 1.0;
            $this->assertTrue($parallel->isPcntl());
        } else {
            $expect = (float) 1.5;
            $this->assertFalse($parallel->isPcntl());
        }

        $this->assertLessThan($expect, $stopwatchResult, "Execution time was {$stopwatchResult}, expected less than {$expect}.\n".(string) $parallel->status());
    }

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::create
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::results
     * @covers Async\Coroutine\Parallel::status
     */
    public function testIt_can_create_and_return_results()
    {
        $parallel = Parallel::create();

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

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::create
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::results
     * @covers Async\Coroutine\Parallel::status
     * @covers Async\Coroutine\Parallel::retry
     */
    public function testIt_can_retry()
    {
        $parallel = Parallel::create();

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

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::status
     */
    public function testIt_can_handle_success()
    {
        $parallel = new Parallel();

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

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::status
     */
    public function testIt_can_handle_timeout()
    {
        $parallel = new Parallel();
        
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

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::concurrency
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     * @covers Async\Coroutine\Parallel::status
     * @covers Async\Coroutine\Parallel::getFinished
     */
    public function testIt_can_handle_a_maximum_of_concurrent_processes()
    {
        $parallel = (new Parallel());

        $parallel->concurrency(2);

        $startTime = microtime(true);

        foreach (range(1, 3) as $i) {
            $parallel->add(function () {
                sleep(1);
            });
        }

        $parallel->wait();

        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        $this->assertGreaterThanOrEqual(2, $executionTime, "Execution time was {$executionTime}, expected more than 2.\n".(string) $parallel->status());
        $this->assertCount(3, $parallel->getFinished(), (string) $parallel->status());
    }

    /**
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::offsetExists
     * @covers Async\Coroutine\Parallel::offsetSet
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::sleepTime
     * @covers Async\Coroutine\Parallel::offsetGet
     * @covers Async\Coroutine\Parallel::offsetUnset
     * @covers \parallel_add
     * @covers \parallel_wait
     */
    public function testIt_can_handle_sleep_array_access()
    {
        $parallel = new Parallel();

        $counter = 0;

        $parallel->sleepTime(10000);

        foreach (range(1, 5) as $i) {
            $parallel[] = \parallel_add(function () {
                usleep(random_int(100, 1000));

                return 2;
            });
        }

        \parallel_wait();

        $this->assertTrue(isset($parallel[0]));

        $array = $parallel[0];

        $this->assertEquals(2, $array->getOutput());

        unset($parallel[0]);

        $this->assertFalse(isset($parallel[0]));
    }

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::offsetSet
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     * @covers \parallel_add
     */
    public function testIt_returns_all_the_output_as_an_array()
    {
        $parallel = new Parallel();

        $result = null;

        foreach (range(1, 5) as $i) {
            $parallel[] = \parallel_add(function () {
                return 2;
            });
        }

        $result = $parallel->wait();

        $this->assertCount(5, $result);
        $this->assertEquals(10, array_sum($result));
    }
    
    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::offsetSet
     * @covers Async\Coroutine\Parallel::__construct
     * @covers Async\Coroutine\Parallel::wait
     * @covers \parallel_add
     * @covers \parallel_wait
     * @covers \parallel_instance
     */
    public function testIt_can_use_a_class_from_the_parent_process()
    {
        $parallel = \parallel_instance();

        /** @var MyClass $result */
        $result = null;

        $parallel[] = \parallel_add(function () {
            $class = new MyClass();

            $class->property = true;

            return $class;
        })->then(function (MyClass $class) use (&$result) {
            $result = $class;
        });

        \parallel_wait();

        $this->assertInstanceOf(MyClass::class, $result);
        $this->assertTrue($result->property);
    }    
    
    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::offsetSet
     * @covers Async\Coroutine\Parallel::wait
     * @covers \parallel_add
     * @covers \parallel_wait
     * @covers \parallel_instance
     */
    public function testIt_works_with_global_helper_functions()
    {
        $workers = \parallel_instance();

        $counter = 0;

        foreach (range(1, 5) as $i) {
            $workers[] = \parallel_add(function () {
                usleep(random_int(10, 1000));

                return 2;
            })->then(function (int $output) use (&$counter) {
                $counter += $output;
            });
        }

        \parallel_wait();

        $this->assertEquals(10, $counter, (string) $workers->status());
    }
	
    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers \parallel_wait
     * @covers \parallel_instance
     */
    public function testIt_can_run_invokable_classes()
    {
        $parallel = \parallel_instance();

        $parallel->add(new InvokableClass());

        $results = \parallel_wait();

        $this->assertEquals(2, $results[0]);
    }

    /**
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Parallel::add
     * @covers \parallel_instance
     */
    public function testIt_reports_error_for_non_invokable_classes()
    {
        $this->expectException(\InvalidArgumentException::class);

        $parallel = \parallel_instance();

        $parallel->add(new NonInvokableClass());
    }
}