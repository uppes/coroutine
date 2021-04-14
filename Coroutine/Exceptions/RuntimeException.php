<?php

namespace Async\Exceptions;

use Async\Exceptions\Panicking;

class RuntimeException extends \RuntimeException implements Panicking
{
}
