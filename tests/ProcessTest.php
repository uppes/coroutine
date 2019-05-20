<?php

namespace Async\Tests;

use Async\Coroutine\Kernel;
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
        $childId = (yield Kernel::taskId());
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
        $childId = yield await([$this, 'childTask']);
        $result = yield Kernel::awaitProcess(function () {
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
        $childId = yield await([$this, 'childTask']);
        $result = null;
        try {
            $result = yield Kernel::awaitProcess(function () {
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
/*
    public function taskProcessTimeOut() 
    {
        $childId = yield await([$this, 'childTask']);
        //$result = null;
        try {
            yield Kernel::awaitProcess(function () {
                usleep(1000);
            }, 0.051);
        } catch (\OutOfBoundsException $error) {
        echo EOL.'here'.$childId;
            $this->mainResult = $childId;
            $this->errorResult = $error;
        }
        //$this->assertNull($result);
        $this->assertEquals($this->mainResult, $childId);
        yield;
    }*/

    /**
     * @covers Async\Coroutine\Coroutine::createTask
     * @covers Async\Coroutine\Coroutine::schedule
     * @covers Async\Coroutine\Coroutine::create
     * @covers Async\Coroutine\Coroutine::runStreams
     * @covers Async\Coroutine\Coroutine::run
     * @covers Async\Coroutine\Coroutine::parallelInstance
     * @covers Async\Coroutine\Coroutine::processInstance
     * @covers Async\Coroutine\Coroutine::createSubProcess
     * @covers Async\Coroutine\Kernel::awaitProcess
     * @covers Async\Coroutine\Process::add
     * @covers Async\Coroutine\Process::processing
     * @covers Async\Coroutine\Parallel::markAsFinished
     * @covers Async\Coroutine\Parallel::markAsTimedOut
     * @covers Async\Coroutine\Parallel::markAsFailed
     * @covers Async\Coroutine\Parallel::add
     * @covers Async\Coroutine\Parallel::create
     * @covers Async\Coroutine\Parallel::await
     * @covers Async\Coroutine\Parallel::results
     * @covers Async\Coroutine\Parallel::status
     */
    public function testProcess() 
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->parallelInstance();

        $coroutine->createTask($this->taskProcess());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(50, $this->counterResult);
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
        $parallel = $coroutine->parallelInstance();

        $coroutine->createTask($this->taskProcessError());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(50, $this->counterResult);
        $this->assertTrue ($this->errorResult instanceof \RuntimeException);
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
    }
/*
    public function testProcessTimeOut() 
    {
        $this->mainResult = 0;
        $this->childId = 0;
        $this->errorResult = null;
        $this->counterResult = 0;

        $coroutine = new Coroutine();
        $parallel = $coroutine->parallelInstance();

        $coroutine->createTask($this->taskProcessTimeOut());
        $coroutine->run();

        $this->assertNotEquals(0, $this->mainResult);
        $this->assertNotEquals(0, $this->childId);
        $this->assertGreaterThan(50, $this->counterResult);
        $this->assertTrue ($this->errorResult instanceof \OutOfBoundsException, (string) $parallel->status());
        $this->assertEquals($this->mainResult, $this->childId, (string) $parallel->status());
    }*/
    
}
