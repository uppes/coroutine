<?php

namespace Async\Coroutine;

use Async\Coroutine\Spawn;
use Async\Processor\Launcher;
use Async\Processor\SerializableException;

class SpawnStatus
{
    protected $spawnPool;

    public function __construct(Spawn $spawnPool)
    {
        $this->spawnPool = $spawnPool;
    }

    public function __toString(): string
    {
        return $this->lines(
            $this->summaryToString(),
            $this->failedToString()
        );
    }

    protected function lines(string ...$lines): string
    {
        return \implode(\PHP_EOL, $lines);
    }

    protected function summaryToString(): string
    {
        $queue = $this->spawnPool->getQueue();
        $finished = $this->spawnPool->getFinished();
        $failed = $this->spawnPool->getFailed();
        $timeouts = $this->spawnPool->getTimeouts();

        return
            'queue: '.\count($queue)
            .' - finished: '.\count($finished)
            .' - failed: '.\count($failed)
            .' - timeout: '.\count($timeouts);
    }

    protected function failedToString(): string
    {
        return (string) \array_reduce($this->spawnPool->getFailed(), function ($currentStatus, Launcher $process) {			
			$output = $process->getErrorOutput();

            if ($output instanceof SerializableException) {
                $output = \get_class($output->asThrowable()).': '.$output->asThrowable()->getMessage();
            }

            return $this->lines((string) $currentStatus, "{$process->getPid()} failed with {$output}");
        });
    }
}
