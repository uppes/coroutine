<?php

namespace Async\Exceptions;

use Async\Exceptions\Panicking;

class InvalidArgumentException extends \InvalidArgumentException implements Panicking
{
}
