<?php

/**
 * @see https://medium.com/@interfacer/intro-to-async-concurrency-in-python-and-node-js-69315b1e3e36
 */
include 'vendor/autoload.php';

/*
```
// async_scrape.js (tested with node 11.3)
const sleep = ts => new Promise(resolve => setTimeout(resolve, ts * 1000));

async function fetchUrl(url) {
    console.log(`~ executing fetchUrl(${url})`);
    console.time(`fetchUrl(${url})`);
    await sleep(1 + Math.random() * 4);
    console.timeEnd(`fetchUrl(${url})`);
    return `<em>fake</em> page html for ${url}`;
}

async function analyzeSentiment(html) {
    console.log(`~ analyzeSentiment("${html}")`);
    console.time(`analyzeSentiment("${html}")`);
    await sleep(1 + Math.random() * 4);
    const r = {
        positive: Math.random()
    }
    console.timeEnd(`analyzeSentiment("${html}")`);
    return r;
}

const urls = [
    "https://www.ietf.org/rfc/rfc2616.txt",
    "https://en.wikipedia.org/wiki/Asynchronous_I/O",
]
const extractedData = {}

async function handleUrl(url) {
    const html = await fetchUrl(url);
    extractedData[url] = await analyzeSentiment(html);
}

async function main() {
    console.time('elapsed');
    await Promise.all(urls.map(handleUrl));
    console.timeEnd('elapsed');
}

main()
```
*/

/*
```
# async_scrape.py (requires Python 3.7+)
import asyncio, random, time

async def fetch_url(url):
    print(f"~ executing fetch_url({url})")
    t = time.perf_counter()
    await asyncio.sleep(random.randint(1, 5))
    print(f"time of fetch_url({url}): {time.perf_counter() - t:.2f}s")
    return f"<em>fake</em> page html for {url}"

async def analyze_sentiment(html):
    print(f"~ executing analyze_sentiment('{html}')")
    t = time.perf_counter()
    await asyncio.sleep(random.randint(1, 5))
    r = {"positive": random.uniform(0, 1)}
    print(f"time of analyze_sentiment('{html}'): {time.perf_counter() - t:.2f}s")
    return r

urls = [
    "https://www.ietf.org/rfc/rfc2616.txt",
    "https://en.wikipedia.org/wiki/Asynchronous_I/O",
]
extracted_data = {}

async def handle_url(url):
    html = await fetch_url(url)
    extracted_data[url] = await analyze_sentiment(html)

async def main():
    t = time.perf_counter()
    await asyncio.gather(*(handle_url(url) for url in urls))
    print("> extracted data:", extracted_data)
    print(f"time elapsed: {time.perf_counter() - t:.2f}s")

asyncio.run(main())
```
*/

function fetch_url($url)
{
  print("~ executing fetch_url($url)" . \EOL);
  \timer_for($url);
  yield \sleep_for(\random_uniform(1, 5));
  print("time of fetch_url($url): " . \timer_for($url) . 's' . \EOL);
  return "<em>fake</em> page html for $url";
};

function analyze_sentiment($html)
{
  print("~ executing analyze_sentiment('$html')" . \EOL);
  \timer_for($html . '.url');
  yield \sleep_for(\random_uniform(1, 5));
  $r = "positive: " . \random_uniform(0, 1);
  print("time of analyze_sentiment('$html'): " . \timer_for($html . '.url') . 's' . \EOL);
  return $r;
};

function handle_url($url)
{
  yield;
  $extracted_data = [];
  $html = yield fetch_url($url);
  $extracted_data[$url] = yield analyze_sentiment($html);
  return yield $extracted_data;
};

function main()
{
  $urls = [
    "https://www.ietf.org/rfc/rfc2616.txt",
    "https://en.wikipedia.org/wiki/Asynchronous_I/O"
  ];

  $urlID = [];

  \timer_for();
  foreach ($urls as $url)
    $urlID[] = yield \away(handle_url($url));

  $result_data = yield \gather($urlID);
  foreach ($result_data as $id => $extracted_data) {
    echo "> extracted data:";
    \print_r($extracted_data);
  }

  print("time elapsed: " . \timer_for() . 's');
}

\coroutine_run(main());
