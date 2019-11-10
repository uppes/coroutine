<?php

namespace Async\Coroutine\Exceptions;

use Async\Coroutine\Exceptions\Panicking;

class InvalidArgumentException extends \InvalidArgumentException implements Panicking
{ }
