<?php

namespace Async\Coroutine\Tasks;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;
use Async\Promise\PromiseInterface;

/**
 * Task that waits for a promise to complete
 */
class PromiseTask extends AbstractTask 
{
    /**
     * Create a new task from the given promise
     * 
     * @param PromiseInterface $promise
     */
    public function __construct(PromiseInterface $promise) 
	{
        // When the promise completes, we want to store the result to be
        // processed on the next tick
        $promise->then(
            function($result) {
                $this->successful = true;
                $this->result = $result;
            },
            function(\Exception $exception) {
                $this->exception = $exception;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doTick(SchedulerInterface $scheduler) 
	{
        /*
         * This is a NOOP for a promise task - all the work is done in the
         * callbacks given to then
         */
    }
}
