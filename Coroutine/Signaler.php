<?php

declare(strict_types=1);

namespace Async;

use Async\CoroutineInterface;

/**
 * A simple manager for signals, modified.
 *
 * @see https://github.com/reactphp/event-loop/blob/master/src/SignalsHandler.php
 *
 * @internal
 */
final class Signaler
{
    private $signals = array();
    private $coroutine;

    public function __construct(CoroutineInterface $coroutine)
    {
        $this->coroutine = $coroutine;
    }

    public function add($signal, $listener)
    {
        if (!isset($this->signals[$signal])) {
            $this->signals[$signal] = array();
        }

        if (\in_array($listener, $this->signals[$signal])) {
            return;
        }

        $this->signals[$signal][] = $listener;
    }

    public function remove($signal, $listener)
    {
        if (!isset($this->signals[$signal])) {
            return;
        }

        $index = \array_search($listener, $this->signals[$signal], true);
        unset($this->signals[$signal][$index]);

        if (isset($this->signals[$signal]) && \count($this->signals[$signal]) === 0) {
            unset($this->signals[$signal]);
        }
    }

    public function execute($signal)
    {
        if (!isset($this->signals[$signal])) {
            return;
        }

        foreach ($this->signals[$signal] as $listener) {
            $this->coroutine->executeTask($listener, $signal);
        }
    }

    public function count($signal)
    {
        if (!isset($this->signals[$signal])) {
            return 0;
        }

        return \count($this->signals[$signal]);
    }

    public function isEmpty()
    {
        return empty($this->signals);
    }
}
