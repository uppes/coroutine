<?php

namespace parallel;

interface EventsInterface extends \Traversable
{
  /* Input */
  public function setInput(InputInterface $input): void;

  /* Targets */
  public function addChannel(ChannelInterface $channel): void;

  public function addFuture(string $name, FutureInterface $future): void;

  public function remove(string $target): void;

  /* Behaviour */
  public function setBlocking(bool $blocking): void;

  public function setTimeout(int $timeout): void;

  /* Polling */
  public function poll(): ?EventsInterface;
}
