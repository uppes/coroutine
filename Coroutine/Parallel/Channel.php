<?php

declare(strict_types=1);

namespace parallel;

use parallel\Channel\Error\Closed;
use parallel\Channel\Error\Existence;
use parallel\Channel\Error\IllegalValue;
use parallel\ChannelInterface;

/**
 * @codeCoverageIgnore
 */
final class Channel implements ChannelInterface
{
    protected static $channels = [];
    protected static $anonymous = 0;
    protected $name = '';
    protected $capacity = null;
    protected $type = null;
    protected $input = null;
    protected $open = true;

    const Infinite = -1;

    /* Anonymous Constructor */
    public function __construct(?int $capacity = null, string $name = __FILE__, bool $anonymous = true)
    {
        if (($capacity < -1) || (!$capacity >= 1))
            if ($capacity !== self::Infinite)
                throw new \TypeError('capacity may be -1 for unlimited, or a positive integer');

        $this->type = empty($capacity) ? 'unbuffered' : 'buffered';
        $this->capacity = (!empty($capacity) || $capacity === self::Infinite) ? $capacity : -1;
        $this->input = new \SplStack;
        if ($anonymous) {
            self::$anonymous++;
            $this->name = $name . '#' . \strlen($name) . '@' . '[' . self::$anonymous . ']';
            self::$channels[self::$anonymous] = $this;
        } else {
            $this->name = $name;
            self::$channels[$name] = $this;
        }
    }

    public function __toString()
    {
        return $this->name;
    }

    /* Access */
    public static function make(string $name, ?int $capacity = null): ChannelInterface
    {
        if (isset(self::$channels[$name]))
            throw new Existence(\sprintf('channel named %s already exists', $name));

        return new self($capacity, $name, false);
    }

    public static function open(string $name): ChannelInterface
    {
        if (isset(self::$channels[$name]))
            return self::$channels[$name];

        throw new Existence(\sprintf('channel named %s not found', $name));
    }

    /* Sharing */
    public function recv()
    {
        if (!$this->input->isEmpty())
            return $this->input->pop();

        if ($this->isClosed()) {
            throw new Closed(\sprintf('channel(%s) closed', $this->name));
        }

        if ($this->type === 'unbuffered')
            return \trim(\fgets($this->ipcInput), \EOL);
    }

    public function send($value): void
    {
        if ($this->isClosed()) {
            throw new Closed(\sprintf('channel(%s) closed', $this->name));
        }

        if (
            \is_object($this->channel)
            && \method_exists($this->channel, 'getProcess')
            && $this->channel->getProcess() instanceof \UVProcess
        ) {
            \uv_write($this->channel->getPipeInput(), self::validateInput(__METHOD__, $value), function () {
            });
        } else {
            $this->input->push(self::validateInput(__METHOD__, $value));
        }
    }

    /* Closing */
    public function close(): void
    {
        if ($this->isClosed()) {
            throw new Closed(\sprintf('channel(%s) already closed', $this->name));
        }

        $this->open = false;
    }

    protected function isClosed(): bool
    {
        return !$this->open;
    }

    /* Constant for Infinitely Buffered */
    /**
     * Validates and normalizes a Process input.
     *
     * @param string $caller The name of method call that validates the input
     * @param mixed  $input  The input to validate
     *
     * @return mixed The validated input
     *
     * @throws IllegalValue In case the input is not valid
     */
    protected static function validateInput(string $caller, $input)
    {
        if (null !== $input) {
            if (\is_resource($input)) {
                return $input;
            }

            if (\is_string($input)) {
                return $input;
            }

            if (\is_scalar($input)) {
                return (string) $input;
            }

            throw new IllegalValue('value is illegal.');
        }

        return $input;
        // @codeCoverageIgnoreEnd
    }
}
