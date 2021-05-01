<?php

namespace Async\Tests;

use function Async\Worker\{add_future, spawn_await};

use Async\Coroutine;
use PHPUnit\Framework\TestCase;

class FutureHandlerTest extends TestCase
{
    protected $mainResult;
    protected $counterResult;
    protected $errorResult;
    protected $childId;

    protected function setUp(): void
    {
        \coroutine_clear();
    }

    public function childTask()
    {
        $childId = (yield \get_task());
        $this->assertNotNull($childId);
        $this->childId = $childId;

        $counter = 0;
        while (true) {
            $counter++;
            yield;
            if ($this->mainResult == $childId)
                break;
        }

        $this->counterResult = $counter;
    }

    public function taskFutureStopAll()
    {
        yield \away(function () {
            yield;
            return '1';
        });

        yield \away($this->childTask());
        yield \away(function () {
            yield yield shutdown(yield \get_task());
        });

        $result = yield add_future(function () {
            usleep(3000);
            return 3;
        });

        $this->mainResult = $result;

        $this->assertNull($result);
    }

    public function taskFuture()
    {
        $childId = yield \away($this->childTask());
        $result = yield add_future(function () {
            usleep(1000);
            return 3;
        });

        $this->mainResult = $result;

        $this->assertNotNull($result);
        $this->assertEquals($result, $childId);
        yield;
    }

    public function taskFutureDisplay()
    {
        $tid = yield \away($this->childTask());
        $result = yield spawn_await(function () {
            echo 'here!';
        }, 10, true);

        $this->mainResult = $tid;
        yield;
    }

    public function taskFutureError()
    {
        $childId = yield \away([$this, 'childTask']);
        $result = null;
        try {
            $result = yield add_future(function () {
                usleep(1000);
                throw new \Exception('3');
            });
        } catch (\RuntimeException $error) {
            $this->mainResult = (int) $error->getMessage();
            $this->errorResult = $error;
        }

        $this->assertNull($result);
        $this->assertEquals($this->mainResult, $childId);
        yield;
    }

    public function taskFutureTimeOut()
    {
        $childId = yield away($this->childTask());
        try {
            yield add_future(function () {
                \sleep(1.5);
            }, 1);
        } catch (\Async\Exceptions\TimeoutError $error) {
            $this->mainResult = $childId;
            $this->errorResult = $error;
        }

        $this->assertEquals($this->mainResult, $childId);
        yield;
    }

    public function testFuture()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $coroutine->createTask($this->taskFuture());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(15, $this->counterResult);
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
        $this->assertEquals($this->mainResult, $parallel->results()[0], (string) $parallel->status());
    }

    public function testFutureDisplay()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $this->expectOutputString('here!');
        $coroutine->createTask($this->taskFutureDisplay());
        $coroutine->run();
    }

    public function testFutureShutDownStopAll()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->counterResult = 0;

        $coroutine = new Coroutine();

        $coroutine->createTask($this->taskFutureStopAll());
        $coroutine->run();

        $this->assertEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertEquals(0, $this->counterResult);
    }

    public function testFutureError()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->errorResult = null;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $coroutine->createTask($this->taskFutureError());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(15, $this->counterResult);
        $this->assertTrue($this->errorResult instanceof \RuntimeException);
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
    }

    public function testFutureTimeOut()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->errorResult = null;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $coroutine->createTask($this->taskFutureTimeOut());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(30, $this->counterResult);
        $this->assertTrue($this->errorResult instanceof \Async\Exceptions\TimeoutError, (string) $parallel->status());
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
    }
}
