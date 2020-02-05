<?php

namespace Async\Tests;

use Async\Coroutine\Coroutine;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
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

    public function taskProcess()
    {
        $childId = yield \away($this->childTask());
        $result = yield \await_process(function () {
            usleep(1000);
            return 3;
        });

        $this->mainResult = $result;

        $this->assertNotNull($result);
        $this->assertEquals($result, $childId);
        yield;
    }

    public function taskProcessError()
    {
        $childId = yield \away([$this, 'childTask']);
        $result = null;
        try {
            $result = yield \await_process(function () {
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

    public function taskProcessTimeOut()
    {
        $childId = yield away($this->childTask());
        try {
            yield \await_process(function () {
                \sleep(1.5);
            }, 1);
        } catch (\Async\Coroutine\Exceptions\TimeoutError $error) {
            $this->mainResult = $childId;
            $this->errorResult = $error;
        }

        $this->assertEquals($this->mainResult, $childId);
        yield;
    }

    public function testProcess()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $coroutine->createTask($this->taskProcess());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(15, $this->counterResult);
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
        $this->assertEquals($this->mainResult, $parallel->results()[0], (string) $parallel->status());
    }

    public function testProcessError()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->errorResult = null;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $coroutine->createTask($this->taskProcessError());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(15, $this->counterResult);
        $this->assertTrue ($this->errorResult instanceof \RuntimeException);
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
    }

    public function testProcessTimeOut()
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->errorResult = null;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->getParallel();

        $coroutine->createTask($this->taskProcessTimeOut());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(30, $this->counterResult);
        $this->assertTrue ($this->errorResult instanceof \Async\Coroutine\Exceptions\TimeoutError, (string) $parallel->status());
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
    }

}
