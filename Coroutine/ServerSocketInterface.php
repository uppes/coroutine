<?php

namespace Async\Coroutine;

interface ServerSocketInterface 
{
    public function accept();
	
    public function read(int $size);

    public function write(string $string);

    public function handshake();

    public function close();    
}
