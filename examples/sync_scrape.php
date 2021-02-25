<?php

/**
 * @see https://medium.com/@interfacer/intro-to-async-concurrency-in-python-and-node-js-69315b1e3e36
 */

/*
```py
# sync_scrape.py (needs Python 3.7+)
import time, re, requests

def fetch_url(url):
    t = time.perf_counter()
    html = requests.get(url).text
    print(f"time of fetch_url({url}): {time.perf_counter() - t:.2f}s")
    return html

def scrape_data(html):
    return re.findall(r'href="([^"]*)"', html)

urls = [
    "http://neverssl.com/",
    "https://www.ietf.org/rfc/rfc2616.txt",
    "https://en.wikipedia.org/wiki/Asynchronous_I/O",
]
extracted_data = {}

t = time.perf_counter()
for url in urls:
    html = fetch_url(url)
    extracted_data[url] = scrape_data(html)

print("> extracted data:", extracted_data)
print(f"time elapsed: {time.perf_counter() - t:.2f}s")
```
*/

/*
```js
// sync_scrape.js (tested with node 11.3)
const request = require("sync-request");

const fetchUrl = url => {
  console.time(`fetchUrl(${url})`);
  const html = request("GET", url).getBody();
  console.timeEnd(`fetchUrl(${url})`);
  return html;
};

const scrapeData = html => {
  const re = /href="([^"]+)"/g;
  const hrefs = [];
  let m;
  while ((m = re.exec(html))) hrefs.push(m[1]);
  return hrefs;
};

const urls = [
  "http://neverssl.com/",
  "https://www.ietf.org/rfc/rfc2616.txt",
  "https://en.wikipedia.org/wiki/Asynchronous_I/O"
];
const extactedData = {};

async function main() {
  console.time("elapsed");
  for (const url of urls) {
    const html = await fetchUrl(url);
    extactedData[url] = scrapeData(html);
  }
  console.log("> extracted data:", extactedData);
  console.timeEnd("elapsed");
}

main();
```
*/

function fetchUrl($url)
{
  $time = microtime(true);
  $html = \file_get_contents($url);
  echo "time of fetchUrl($url): " . (microtime(true) - $time) . \PHP_EOL;
  return $html;
};

function scrapeData($html)
{
  \preg_match_all('/href="([^"]+)"/', $html, $match);
  return $match[1];
};

$urls = [
  "http://neverssl.com/",
  "https://www.ietf.org/rfc/rfc2616.txt",
  "https://en.wikipedia.org/wiki/Asynchronous_I/O"
];

$extractedData = [];

$elapsed = microtime(true);
foreach ($urls as $url) {
  $html = fetchUrl($url);
  $extractedData[$url] = scrapeData($html);
}
$totalTime = (microtime(true) - $elapsed);

echo "> extracted data:";
print_r($extractedData);
echo '> time elapsed: ' . $totalTime . \PHP_EOL;
