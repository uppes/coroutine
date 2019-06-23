<?php

namespace Async\Coroutine;

interface FileStreamInterface
{
    public function fileOpen(string $uri = null, string $mode = 'r', $context = []);

    public function fileContents(int $size = 256, float $timeout_seconds = 0.5);

    public function fileCreate($contents);

    public function fileLines();

    public function fileMeta(): ?array;

    public function fileStatus(array $meta = null);

    public function fileValid(): bool;

    public function fileClose();

    public function fileHandle();
    
    public static function instance(): self;
}
