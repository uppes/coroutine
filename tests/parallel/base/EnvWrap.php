<?php

declare(strict_types=1);

namespace Async\Tests\parallel\base;

use Async\Tests\parallel\base\EnvDto;

class EnvWrap
{
    private EnvDto $env;

    public function __construct($env)
    {
        $this->env = $env;
    }

    public function getEnv(): EnvDto
    {
        return $this->env;
    }
}

spl_autoload_register(static function ($fcqn) {
    if ('EnvDto' === $fcqn) {
        require sprintf('%s/062.immutable.inc', __DIR__);
    }
});
