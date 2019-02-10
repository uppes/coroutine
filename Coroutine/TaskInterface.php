<?php

namespace Async\Coroutine;

interface TaskInterface
{
    public function taskId();

    public function sendValue($sendValue);

    public function exception($exception);

    public function run();

    public function isFinished();
}
