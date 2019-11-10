<?php

namespace Async\Coroutine\Exceptions;

use Async\Coroutine\Exceptions\Panicking;

class RuntimeException extends \RuntimeException implements Panicking
{ }
