<?php

namespace Async\Coroutine;

interface ClientSocketInterface 
{
    public static function create(string $uri = null, array $context = []);

    public function meta(): ?array;

    public function read(int $size = -1);

    public function write($string);

    public function close();

    public function valid(): bool;

    public function handle();

    public static function instance(): self;
}
