<?php

namespace Async\Coroutine\Exceptions;

use Async\Coroutine\Exceptions\Panicking;

class LengthException extends \LengthException implements Panicking
{ }
