<?php

/**
 * @see https://github.com/amphp/artax/blob/master/examples/6-parallel-requests.php
 */
include 'vendor/autoload.php';

function requestHandler(string $uri)
{
    return yield [$uri, yield \file_get($uri)];
};

function main()
{
    $uris = [
        "https://google.com/",
        "https://github.com/",
        "https://stackoverflow.com/"
    ];

    try {
        $uriId = [];

        foreach ($uris as $uri) {
            $uriId[] = yield \away(\requestHandler($uri));
        }

        $bodies = yield \gather($uriId);

        foreach ($bodies as $id => $result) {
            [$uri, $body] = $result;
            print "Task $id: " . $uri . " - " . \strlen($body) . " bytes" . \EOL;
        }
    } catch (\Exception $error) {
        // If something goes wrong Amp will throw the exception where the promise was yielded.
        // The Client::request() method itself will never throw directly, but returns a promise.
        echo 'There was a problem: ' . $error->getMessage();
    }

    yield shutdown();
}

\coroutine_run(\main());
