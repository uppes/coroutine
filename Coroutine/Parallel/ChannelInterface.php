<?php

namespace Async\Parallel;

interface ChannelInterface
{
  /* Constant for Infinitely Buffered */
  const Infinite = '';

  /* Anonymous Constructor */
  /**
   * Shall make an anonymous buffered/unbuffered channel with the given capacity
   *
   * @param integer|null $capacity
   */
  public function __construct(?int $capacity = null);

  /* Access */
  /**
   * Shall make a buffered/unbuffered channel with the given name and capacity
   *
   * @param string $name
   * @param integer|null $capacity
   * @return ChannelInterface
   */
  public function make(string $name, ?int $capacity = null): ChannelInterface;

  /**
   * Shall open the channel with the given name
   *
   * @param string $name
   * @return ChannelInterface
   */
  public function open(string $name): ChannelInterface;

  /* Sharing */
  /**
   * Shall recv a value from this channel
   *
   * @return void
   */
  public function recv();

  /**
   * Shall send the given value on this channel
   *
   * @param mixed $value
   * @return void
   */
  public function send($value): void;

  /* Closing */
  /**
   * Shall close this channel
   *
   * @return void
   */
  public function close(): void;
}
