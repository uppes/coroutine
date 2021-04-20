<?php

declare(strict_types=1);

namespace parallel;

use parallel\Runtime;
use parallel\FutureInterface;
use parallel\Runtime\Error\Bootstrap;

if (!\function_exists('functional_api')) {
  /**
   * Shall schedule task for execution in parallel, passing argv at execution time.
   *
   * @param \closure $task
   * @param mixed ...$argv
   * @return FutureInterface|null
   *
   * @codeCoverageIgnore
   */
  function run(\closure $task, ...$argv): ?FutureInterface
  {
    global $___bootstrap___, $___run___;
    $___run___ = new Runtime($___bootstrap___);
    return $___run___->run($task, ...$argv);
  }

  /**
   * Shall use the provided file to bootstrap all runtimes created for
   * automatic scheduling via parallel\run().
   *
   * @param string|null $file
   * @return void
   *
   * @codeCoverageIgnore
   */
  function bootstrap(?string $file = null)
  {
    global $___bootstrap___, $___run___;
    if (empty($___bootstrap___) && $___run___ instanceof FutureInterface)
      throw new Bootstrap('should be called once, before any calls to \parallel\run');

    if (isset($___bootstrap___))
      throw new Bootstrap(\sprintf('\parallel\bootstrap already set to %s', $___bootstrap___));

    $___bootstrap___ = $file;
  }

  /**
   * @codeCoverageIgnore
   */
  function functional_api()
  {
    return true;
  }
}
