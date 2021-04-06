# Concurrency and PHP in relation to modern programming languages, Python, Go, NodeJS, Rust, Etc

This post is to address an misconception about **PHP**. It is inspired by this multi-part series from [Concurrency in modern programming languages](https://dev.to/deepu105/concurrency-in-modern-programming-languages-introduction-ckk) on building and benchmarking a concurrent web server. The series covers in details the behavior working of concurrency with the languages of **Rust**, **Go**, **Javascript/NodeJS**, **TypeScript/Deno**, **Kotlin**, and **Java**. Not sure why **Python** excluded?

I will include the source code for each language, then a **PHP** version with the same simplicity in code and then benchmarks results.

I will start off with one think in mind, if an developer had some computer science or related like study they will find all languages essential are the same, some may already include pre-made routine solutions for problems. If theres no pre-made solution, it can be written in the language of choice to get the same outcome and behavior, taken from the another language. Trying to get the same performance is a different story, but the concept stays the same, the syntax is different.

For the details of the following code block see [Concurrency in modern programming languages: Rust](https://dev.to/deepu105/concurrency-in-modern-programming-languages-rust-19co).

```rs
#[async_std::main]
async fn main() {
    let listener = TcpListener::bind("127.0.0.1:8080").await.unwrap(); // bind listener
    let mut count = 0; // count used to introduce delays

    loop {
        count = count + 1;
        // Listen for an incoming connection.
        let (stream, _) = listener.accept().await.unwrap();
        // spawn a new task to handle the connection
        task::spawn(handle_connection(stream, count));
    }
}

async fn handle_connection(mut stream: TcpStream, count: i64) {
    // Read the first 1024 bytes of data from the stream
    let mut buffer = [0; 1024];
    stream.read(&mut buffer).await.unwrap();

    // add 2 second delay to every 10th request
    if (count % 10) == 0 {
        println!("Adding delay. Count: {}", count);
        task::sleep(Duration::from_secs(2)).await;
    }

    let header = "
HTTP/1.0 200 OK
Connection: keep-alive
Content-Length: 174
Content-Type: text/html; charset=utf-8
    ";
    let contents = fs::read_to_string("hello.html").unwrap();

    let response = format!("{}\r\n\r\n{}", header, contents);

    stream.write(response.as_bytes()).await.unwrap(); // write response
    stream.flush().await.unwrap();
}
```

For the details of the following code block see [Concurrency in modern programming languages: Golang](https://dev.to/deepu105/concurrency-in-modern-programming-languages-golang-439i).

```go
func main() {
    var count = 0
    // set router
    http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
        defer r.Body.Close()
        count++
        handleConnection(w, count)
    })
    // set listen port
    err := http.ListenAndServe(":8080", nil)
    if err != nil {
        log.Fatal("ListenAndServe: ", err)
    }
}

func handleConnection(w http.ResponseWriter, count int) {
    // add 2 second delay to every 10th request
    if (count % 10) == 0 {
        println("Adding delay. Count: ", count)
        time.Sleep(2 * time.Second)
    }
    html, _ := ioutil.ReadFile("hello.html") // read html file
    w.Header().Add("Connection", "keep-alive")
    w.WriteHeader(200)           // 200 OK
    fmt.Fprintf(w, string(html)) // send data to client side
}
```

For the details of the following code block see [Concurrency in modern programming languages: JavaScript on NodeJS](https://dev.to/deepu105/concurrency-in-modern-programming-languages-javascript-on-nodejs-epo).

```js
const http = require("http");
const fs = require("fs").promises;

let count = 0;

// set router
const server = http.createServer((req, res) => {
  count++;
  requestListener(req, res, count);
});

const host = "localhost";
const port = 8080;

// set listen port
server.listen(port, host, () => {
  console.log(`Server is running on http://${host}:${port}`);
});

const requestListener = async function (req, res, count) {
  // add 2 second delay to every 10th request
  if (count % 10 === 0) {
    console.log("Adding delay. Count: ", count);
    await sleep(2000);
  }
  const contents = await fs.readFile(__dirname + "/hello.html"); // read html file
  res.setHeader("Connection", "keep-alive");
  res.writeHead(200); // 200 OK
  res.end(contents); // send data to client side
};

function sleep(ms) {
  return new Promise((resolve) => {
    setTimeout(resolve, ms);
  });
}
```

For the details of the following code block see [Concurrency in modern programming languages: TypeScript on Deno](https://dev.to/deepu105/concurrency-in-modern-programming-languages-typescript-on-deno-hkb).

```ts
import { serve, ServerRequest } from "https://deno.land/std/http/server.ts";

let count = 0;

// set listen port
const server = serve({ hostname: "0.0.0.0", port: 8080 });
console.log(`HTTP webserver running at:  http://localhost:8080/`);

// listen to all incoming requests
for await (const request of server) handleRequest(request);

async function handleRequest(request: ServerRequest) {
  count++;
  // add 2 second delay to every 10th request
  if (count % 10 === 0) {
    console.log("Adding delay. Count: ", count);
    await sleep(2000);
  }
  // read html file
  const body = await Deno.readTextFile("./hello.html");
  const res = {
    status: 200,
    body,
    headers: new Headers(),
  };
  res.headers.set("Connection", "keep-alive");
  request.respond(res); // send data to client side
}

// sleep function since NodeJS doesn't provide one
function sleep(ms: number) {
  return new Promise((resolve) => {
    setTimeout(resolve, ms);
  });
}
```

Here we have the **PHP** version. For this to work as posted, an external package [Coroutine](https://symplely.github.io/coroutine/) is required.
> This script is hosted on [Github]()

```php
include 'vendor/autoload.php';

use function Async\Path\file_get;
use function Async\Stream\{messenger_for, net_accept, net_close, net_local, net_response, net_server, net_write};

function main($port)
{
  $count = 0;
  $server = yield net_server($port);
  print('Server is running on: ' . net_local($server) . \EOL);

  while (true) {
    $count++;
    // Will pause current task and wait for connection, all others tasks will continue to run
    $connected = yield net_accept($server);
    // Once an connection is made, will create new task and continue execution there, will not block
    yield away(handleClient($connected, $count));
  }
}

function handleClient($socket, int $counter)
{
  yield stateless_task();
  // add 2 second delay to every 10th request
  if ($counter % 10 === 0) {
    print("Adding delay. Count: " . $counter . \EOL);
    yield sleep_for(2);
  }

  $html = messenger_for('response');
  $contents = yield file_get('hello.html');
  if (is_string($contents)) {
    $output = net_response($html, $contents, 200);
  } else {
    $output = net_response($html, "The file you requested does not exist. Sorry!", 404);
  }

  yield net_write($socket, $output);
  yield net_close($socket);
}

coroutine_run(main(8080));
```

## The Execution Flow, Under The Hood

The first thing you might notice, is the usage of [yield](https://en.wikipedia.org/wiki/Yield_(multithreading)).

Normally the **yield** statement turns the surrounding _function/method_ into a [generator](https://en.wikipedia.org/wiki/Generator_(computer_programming)) **object** to return to the _user_. In which case, the _user_ is then required to step-thru to have the _function/method_ instructions executed.

This script gets started by calling `coroutine_run()`.

This inturn will add the `main()` **generator object** into an [Queue](https://en.wikipedia.org/wiki/Queue_(abstract_data_type)), but before adding it _wraps_ the object into another class [Task](https://en.wikipedia.org/wiki/Task_(computing)) to use instead.

- This `Task` class is responsible for keeping track of the _generator_ **state/status**, it's running by invoking `->current()`, `->send()`, `->throw()` and storing any **results**.
- The `Task` class will then register the passed in `generator` with a single universal [scheduling](https://en.wikipedia.org/wiki/Scheduling_(computing)) routine, a [Coroutine](https://en.wikipedia.org/wiki/Coroutine) class method process to step-thru **all** *generator* objects, that uses an different [Stack](https://en.wikipedia.org/wiki/Stack_(abstract_data_type)) to manage itself.

The `yield` statement now just leaves or returns in any context with **results**, it's an [control flow](https://en.wikipedia.org/wiki/Control_flow) mechanism, it suspends and resumes where it left off.

More exactly, it marks an exact place where our code gives up control, it signals that it's ready to be added to a waiting list, meanwhile, the *CPU/Application* can shift to other `tasks`.

The next step that happens in our execution flow, is adding the main `supervisor task`a into the **Queue**.

- This `supervisor task` for *checking* __streams/sockets__, __timers__, __processes__, __signals__, and __events__, or just *waiting* for them to happen. Each of these *checks* have there own *key–value* pair store [array](https://en.wikipedia.org/wiki/Array_data_structure).

There will be up to **9** various [abstract data types](https://en.wikipedia.org/wiki/Abstract_data_type) happening, only one holds the `task` to be executed next, the **Queue**.

By using a [functional programming](https://en.wikipedia.org/wiki/Functional_programming) paradigm where [function composition](https://en.wikipedia.org/wiki/Function_composition_(computer_science)) is mixed with [mutual recursion](https://en.wikipedia.org/wiki/Mutual_recursion), we will get `true` **PHP** concurrency as I have here, it's based on **Python's** [original model](https://www.python.org/dev/peps/pep-0342/) of using [@decorators](https://en.wikipedia.org/wiki/Python_syntax_and_semantics#Decorators) on generator functions, that eventually lead to reserve words `async/await`, just [syntactic sugar](https://en.wikipedia.org/wiki/Syntactic_sugar).

For an general overview of the power of `Generators` watch: <!-- {% youtube Z_OAlIhXziw %} -->
[![Curious Course on Coroutines and Concurrency](https://img.youtube.com/vi/Z_OAlIhXziw/default.jpg)](https://www.youtube.com/watch?v=Z_OAlIhXziw) by [David Beazley](http://www.dabeaz.com/coroutines/)

___Q: What's the underlying concept of why calling `await` outside a `function` not created with `async` throws a **syntax error**?___

- The languages that has these constructed calls, internally see them as a special **private** class calls. You would get similar error calling any class private/protect methods directly.

## The Promise Problem, We Don't Make Any

Normally, when hearing of [asynchronous programming](https://en.wikipedia.org/wiki/Asynchrony_(computer_programming)), an [event loop](https://en.wikipedia.org/wiki/Event_loop), [callbacks](https://en.wikipedia.org/wiki/Callback_(computer_programming)), [promises/futures](https://en.wikipedia.org/wiki/Futures_and_promises), and [threading](https://en.wikipedia.org/wiki/Thread_(computing)) has to be addressed or come into play to deal with the **blocking** nature of certain **OS**, **Hardware** features.

**There are many actions when looked at very technically, and by it's actual behavior, can be described using different terms.**

In computer science, the _event loop_ is a programming construct that waits for events (triggers) and then performs specific (programmed) actions. A _promise/future_ is also programming construct that is better at handling callbacks after some routine is finish, but on it's initial use, it returns an `object`. Both of these constructs runs in an single thread, they are use together to orchestrate handling blocking code.

So when we use `yield` within any function, that `function` can now be seen as a `Promise`, we can't do anything until we take the returned `object` and step-thru it. This `object` is a language feature, that already has a [looping](https://en.wikipedia.org/wiki/Infinite_loop) procedure process, and state management. With each [tick](https://en.wikipedia.org/wiki/Instruction_cycle) of the stepping thru, an whole bunch of things can be preformed. This is where our **main** `supervisor task` steps in.

The `supervisor task` does _check_, will conditionally _wait_, but there is no `task` execution here, just the _actions_ to `schedule` the **Task** back into the **Queue**, if a _check_ is _triggered_.

- `Task` objects that is not completed will be **rescheduled**.
- This `Event Loop` is **task**, that is always `yield`ing.
- This `Event Loop` is an natural `Generator` process.

## Handling The Blocking Conundrum. What, Wait Some More?

Now we come to a fork, where we need to request hardware or a OS feature, and still allow other things to processed. We have routines to address these requests, they are part of the `Coroutine` class. Each routine require an callback function to execute after completion. The internal instructions of these routines are **PHP** built-in `native` or an external `extension` library functions.

To handle these instructions and constraints, we need to *bridge* them together, and mix in a `Task` with the help of another class, the [Kernel](https://en.wikipedia.org/wiki/Kernel_(operating_system)). The `kernel` does the [trampoline](https://en.wikipedia.org/wiki/Trampoline_(computing)), it is what initiates the recursion, and any [system call](https://en.wikipedia.org/wiki/System_call). **Python** has a [decorator](https://realpython.com/primer-on-python-decorators/) process that can change the behavior of any function. **PHP** has something similar, any class can create an [magic method](https://www.php.net/manual/en/language.oop5.magic.php), one of such, can [invoke](https://www.php.net/manual/en/language.oop5.magic.php#object.invoke) itself, the object returned, instantiated.

Most of the time you will be making `kernel` calls to get anything performed.
The `kernel` is what gives the `supervisor task`, our __Event Loop__ the list of _checks_ to trigger on.

Let's pull out 3 things that our concurrent web server will do.

- Get an socket, read from file, write to socket.

Each of these has the potentials to block. So instead we take an different route, which depends on the platform we're using. Under **Windows** and _native_ **PHP**, local file request resources can't be put in non-blocking mode. **Linux**, does not have this restriction.

The process we take for any _request/action_, that has a blocking nature, is to take that action's associated  `resource` and tie it to a `Task` and _store_, then proceed to next `Task` in the **Queue**. The _stored_ resource _pair_ will be processed by `supervisor task`.

- For maximum performance we can arrange blocking code to run in an separate **thread**.
In order to achieve, an [cross-platform](http://docs.libuv.org/en/v1.x/) library [libuv](https://github.com/libuv/libuv) functions has been incorporated, the **PHP** [ext-uv](https://github.com/amphp/ext-uv) extension will need to be installed. Once installed, the `supervisor task` will call [uv_run()](http://docs.libuv.org/en/v1.x/loop.html#c.uv_run) to execute **libuv** [event loop](http://docs.libuv.org/en/v1.x/loop.html).

- In case **libuv** not installed, on **Windows**, some `requests/actions` will be executed in an separate [child-process](https://en.wikipedia.org/wiki/Child_process), if not able to be put in non-blocking mode. **Linux** does not have this issue, all `requests/actions` can be set to non-blocking. Thereafter, the `supervisor task` will perform [stream_select](https://www.php.net/manual/en/function.stream-select.php) call.

When the `supervisor task` has nothing to _check_ and nothing in the **Queue**, the whole system here will just stop and exit.

Since we are opening an `socket` connection, this script will not stop running.

### Running Multiple Tasks

Now, let's take a quick look to how to work with a bunch of `tasks`, which is the whole point of **Concurrency**.

Take this block of code from [Python Asyncio: Basic Fundamentals](https://dev.to/v_it_aly/asyncio-basic-fundamentals-4i5m).

It shows how to schedule a few tasks using [asyncio.gather()](https://docs.python.org/3/library/asyncio-task.html#asyncio.gather), and [asyncio.sleep()](https://docs.python.org/3/library/asyncio-task.html#sleeping) is used to imitate waiting for a web response:

```py
import asyncio

async def task(num: int):
    print(f"Task {num}: request sent")
    await asyncio.sleep(1)
    print(f"Task {num}: response arrived")

async def main():
    await asyncio.gather(*[task(x) for x in range(1,3)])

if __name__ == '__main__':
    asyncio.run(main())
```

Will output:

```test
Task 1: request sent
Task 2: request sent
Task 3: request sent
Task 1: response arrived
Task 2: response arrived
Task 3: response arrived
```

This **PHP** version will produce same output.

- The `gather()` and `sleep_for()` functions was created to behave same as **Python's** specs have them.

```php
include 'vendor/autoload.php';

function task(int $num) {
  print("Task {$num}: request sent" . EOL);
  yield sleep_for(1);
  print("Task {$num}: response arrived" . EOL);
}

function main() {
  yield gather(task(1), task(2), task(3));
}

coroutine_run(main());
/* `main` could have also written been like:
function main() {
  $tid = [];
  foreach(range(1, 3) as $i) {
    $tid[] = task($i);
  }

  yield gather($tid);
}
*/
```

- todo go over all functions in our web-sever script

- todo show benchmark under Windows 10, PHP 8 no libuv, since currently no build version available
- todo show benchmark under Windows 10, PHP 7.4 with libuv
- todo show benchmark under WSL - Linux on Windows, PHP 7.3 with libuv
- todo show benchmark under Raspberry Pi, PHP 7.3 with libuv

---

If you watch the above __video__ [Curious Course on Coroutines and Concurrency](https://www.youtube.com/watch?v=Z_OAlIhXziw) very closely starting at [1:49:30](https://youtu.be/Z_OAlIhXziw?t=6570), and then reference **Nikita Popov** [Cooperative multitasking using coroutines (in PHP!)](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html) _article/post_, seems most of what he introduced about `yield`, [generators](http://php.net/generators) into [PHP 5.5](https://www.php.net/releases/5_5_0.php) originated from.

The only thing that seem strange, why _at the end_ he states:
>"When I first heard about all this I found this concept totally awesome and that’s what motivated me to implement it in PHP. At the same time I find coroutines really scary. There is a thin line between awesome code and a total mess and I think coroutines sit exactly on that line. It’s hard for me to say whether writing async code in the way outlined above is really beneficial."

**What?** From that time period to now, **Python** created an whole ecosystem around the concept, totally developed `async/await` from it.

- For a short historical building block view read [Python Concurrency: Making sense of asyncio](https://dev.to/educative/python-concurrency-making-sense-of-asyncio-4a1b).

---

There is an big issue with **PHP** and why it might be getting looked down on.
Basically, to me the **ad-hoc** nature of it's origins, and how many users still write code, I mean just ***copy***, just ***follow***. They seem to have no computer science like background study, in which case, this might be of interest:

[Teach Yourself Computer Science](https://teachyourselfcs.com/), goes into **Why learn computer science?** and recommending:

- [Computer Science 61A, 001 - Spring 2011](https://archive.org/details/ucberkeley-webcast-PL3E89002AA9B9879E) From **[archive.org](https://archive.org/details/ucberkeley-webcast)** - Video Lectures
- [Structure and Interpretation of Computer Programs](https://ocw.mit.edu/courses/electrical-engineering-and-computer-science/6-001-structure-and-interpretation-of-computer-programs-spring-2005/video-lectures/) From **[mit.edu](https://ocw.mit.edu/courses/electrical-engineering-and-computer-science/)** - Video Lectures
