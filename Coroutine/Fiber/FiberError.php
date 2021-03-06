<?php

namespace Async;

/**
 * Exception thrown due to invalid fiber actions, such as resuming a terminated fiber.
 */
final class FiberError extends \Error
{
}
