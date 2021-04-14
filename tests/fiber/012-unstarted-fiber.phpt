--TEST--
Not starting a fiber does not leak
--FILE--
<?php

require 'vendor/autoload.php';

use Async\Fiber;

function main()
{
$fiber = new Fiber(function() { return null;});
echo "done";
}

\coroutine_run(main());

--EXPECT--
done
