<?php

declare(strict_types=1);

namespace Async\Parallel;

interface EventType
{
    /* Event::$object was read into Event::$value */
    const Read = '';
    /* Input for Event::$source written to Event::$object */
    const Write = '';
    /* Event::$object (Channel) was closed */
    const Close = '';
    /* Event::$object (Future) was cancelled */
    const Cancel = '';
    /* Runtime executing Event::$object (Future) was killed */
    const Kill = '';
    /* Event::$object (Future) raised error */
    const Error = '';
}
