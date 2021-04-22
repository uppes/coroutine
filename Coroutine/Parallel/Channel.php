<?php

declare(strict_types=1);

namespace parallel;

use Closure;
use Async\Spawn\Channeled;
use Async\Spawn\ChanneledInterface;
use parallel\Channel\Error\Closed;
use parallel\Channel\Error\Existence;
use parallel\Channel\Error\IllegalValue;
use stdClass;

/**
 * @codeCoverageIgnore
 */
final class Channel extends Channeled
{
    const Infinite = -1;

    protected static $channels = [];
    protected static $anonymous = 0;
    protected $name = '';
    protected $capacity = null;
    protected $type = null;
    protected $buffered = null;
    protected $open = true;

    protected $input = \STDIN;
    protected $output = \STDOUT;
    protected $error = \STDERR;

    /* Anonymous Constructor */
    /**
     * Shall make an anonymous buffered/unbuffered channel with the given capacity
     *
     * @param integer|null $capacity
     */
    public function __construct(
        ?int $capacity = null,
        string $name = __FILE__,
        bool $anonymous = true
    ) {
        \stream_set_read_buffer($this->input, 0);
        \stream_set_write_buffer($this->output, 0);
        \stream_set_read_buffer($this->error, 0);
        \stream_set_write_buffer($this->error, 0);
        if (($capacity < -1) || ($capacity == 0))
            throw new \TypeError('capacity may be -1 for unlimited, or a positive integer');

        $this->type = empty($capacity) ? 'unbuffered' : 'buffered';
        $this->capacity = $capacity;
        $this->buffered = new \SplQueue;
        if ($anonymous) {
            self::$anonymous++;
            $this->name = \sprintf("%s#%u@%d[%d]", $name, __LINE__, \strlen($name), self::$anonymous);
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
    public static function make(string $name, ?int $capacity = null): ChanneledInterface
    {
        if (isset(self::$channels[$name]))
            throw new Existence(\sprintf('channel named %s already exists', $name));

        return new self($capacity, $name, false);
    }

    public static function open(string $name): ChanneledInterface
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
            null !== $value
            && \is_object($this->channel)
            && \method_exists($this->channel, 'getProcess')
            && $this->channel->getProcess() instanceof \UVProcess
        ) {
            \uv_write($this->channel->getPipeInput(), self::validateInput(__FUNCTION__, $value), function () {
            });
        } elseif (null !== $value) {
            \fwrite($this->output, (string) $value);
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

    public function isClosed(): bool
    {
        return !$this->open;
    }

    /**
     * @throws IllegalValue In case the input is not valid
     */
    protected static function validateInput(string $caller, $input)
    {
        if (null !== $input) {
            if (\is_string($input) || \is_scalar($input) || $input instanceof Closure || $input instanceof stdClass) {
                return $input;
            }

            throw new IllegalValue('value is illegal.');
        }

        return $input;
        // @codeCoverageIgnoreEnd
    }
}
