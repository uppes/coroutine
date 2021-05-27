<?php

namespace Async\examples\parallel;

use \parallel\Runtime;
use \parallel\Channel;

class BackgroundLogger
{
  private $channel;
  private $runtime;

  public function __construct(string $file)
  {
    $this->runtime = new Runtime();
    $this->channel = Channel::make($file, Channel::Infinite);

    $this->runtime->run(function ($file) {
      $channel = Channel::open($file);
      $handle = fopen($file, "rb");

      if (!is_resource($handle)) {
        throw new \RuntimeException("could not open {$file}");
      }

      while (($input = $channel->recv())) {
        fwrite($handle, "{$input}\n");
      }

      fclose($handle);
    }, $file);
  }

  public function log($message, ...$args)
  {
    $this->channel->send(vsprintf($message, $args));
  }

  public function __destruct()
  {
    $this->channel->send(false);
  }
}
