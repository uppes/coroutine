<?php

include 'vendor/autoload.php';

use Async\Fiber;

/*
class EventLoop
{
    private $nextId = 'a';
    private $deferCallbacks = [];
    private $read = [];
    private $streamCallbacks = [];

    public function run(): void
    {
        while (!empty($this->deferCallbacks) || !empty($this->read)) {
            $defers = $this->deferCallbacks;
            $this->deferCallbacks = [];
            foreach ($defers as $id => $defer) {
                $defer();
            }

            $this->select($this->read);
        }
    }

    private function select(array $read): void
    {
        $timeout = empty($this->deferCallbacks) ? null : 0;
        if (!stream_select($read, $write, $except, $timeout, $timeout)) {
            return;
        }

        foreach ($read as $id => $resource) {
            $callback = $this->streamCallbacks[$id];
            unset($this->read[$id], $this->streamCallbacks[$id]);
            $callback($resource);
        }
    }
    public function defer(callable $callback): void
    {
        $id = $this->nextId++;
        $this->deferCallbacks[$id] = $callback;
    }

    public function read($resource, callable $callback): void
    {
        $id = $this->nextId++;
        $this->read[$id] = $resource;
        $this->streamCallbacks[$id] = $callback;
    }
}
*/

function main()
{
    [$read, $write] = stream_socket_pair(
        stripos(PHP_OS, 'win') === 0 ? STREAM_PF_INET : STREAM_PF_UNIX,
        STREAM_SOCK_STREAM,
        STREAM_IPPROTO_IP
    );

    // Set streams to non-blocking mode.
    stream_set_blocking($read, false);
    stream_set_blocking($write, false);

    // $loop = new EventLoop;

    // Read data in a separate fiber after checking if the stream is readable.
    $fiber = new Fiber(function () use ($read) {
        echo "Waiting for data...\n";

        $fiber = Fiber::this();
        yield away(function () use ($read, $fiber) {
            yield read_wait($read);
            yield $fiber->resume();
        });

        yield Fiber::suspend();

        $data = fread($read, 8192);

        echo "Received data: ", $data, "\n";
    });

    // Start the fiber, which will suspend while waiting for a read event.
    yield $fiber->start();

    // Defer writing data to an event loop callback.
    fwrite($write, "Hello, world!");

    // Run the event loop.
    // $loop->run();
    yield;
}

\coroutine_run(main());
