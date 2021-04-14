<?php

namespace Async\Exceptions;

use Async\Exceptions\Panicking;

class LengthException extends \LengthException implements Panicking
{
}
