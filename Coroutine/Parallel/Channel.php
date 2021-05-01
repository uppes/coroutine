<?php

declare(strict_types=1);

namespace parallel;

use Async\Spawn\Channeled;
use parallel\Channel\Error\Closed;
use parallel\Channel\Error\Existence;
use parallel\Channel\Error\IllegalValue;

final class Channel extends Channeled
{
    public static function throwExistence(string $errorMessage): void
    {
        throw new Existence($errorMessage);
    }

    public static function throwClosed(string $errorMessage): void
    {
        throw new Closed($errorMessage);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function throwIllegalValue(string $errorMessage): void
    {
        throw new IllegalValue($errorMessage);
    }
}
