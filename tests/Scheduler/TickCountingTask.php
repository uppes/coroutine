<?php

namespace Async\Tests\Scheduler;

use Async\Coroutine\SchedulerInterface;
use Async\Coroutine\Tasks\AbstractTask;

/**
 * Task that is complete after tick has been invoked a certain number of times
 */
class TickCountingTask extends AbstractTask 
{
    protected $times = 0;
    protected $tickCount = 0;
    
    public function __construct($times) {
        $this->times = $times;
    }
    
    public function getTickCount() {
        return $this->tickCount;
    }
    
    public function isSuccessful() {
        return $this->tickCount >= $this->times;
    }
    
    public function tick(SchedulerInterface $scheduler) {
        // We override tick to do the counting so that we can add assertions on
        // the number of times that doTick is called
        parent::tick($scheduler);
        $this->tickCount++;
    }
    
    protected function doTick(SchedulerInterface $scheduler) { /* NOOP */ }
}
