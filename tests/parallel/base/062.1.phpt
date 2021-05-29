--TEST--
parallel immutable class load
--SKIPIF--
<?php
if (!extension_loaded('uv')) {
	echo 'skip';
}
if (!version_compare(PHP_VERSION, "7.4", ">=")) {
    die("skip php 7.4 required");
}
?>
--FILE--
<?php
declare(strict_types = 1);
include 'vendor/autoload.php';

use parallel\Runtime;

use Async\Tests\parallel\base\EnvDto;
use Async\Tests\parallel\base\EnvWrap;

$rt = new Runtime();
$params = new EnvWrap(new EnvDto('ok'));

$rt->run(static function (EnvWrap $params) {
    echo $params->getEnv()->getName();
}, $params);
?>
--EXPECT--
ok
