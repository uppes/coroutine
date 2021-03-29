<?php

namespace Async\Coroutine;

/**
 * Exception thrown when destroying a fiber. This exception cannot be caught by user code.
 */
final class FiberExit extends \Exception
{
}
