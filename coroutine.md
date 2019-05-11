# Coroutine

A Coroutine are special functions that are base on __generators__, with the use of `yield` and `yield from`. When used they release the flow of control back to the calling function, with bring with an object.

This package represent that calling function, an __scheduler__, similar to an **event loop**. A coroutine needs to be scheduled to run, and once scheduled coroutines are wrapped in an `Task`, which are a type of **Promise**.

A `task` is an object that represents some work to be done, potentially with a result at the end of it. These tasks are _registered_ with a scheduler that is responsible for running them.

Due to the __single-threaded__ nature of PHP (without extensions anyway), we cannot think of a `task` as doing a single __long-running__ calculation - this will __block__ the single thread until the task is finished.

Instead, `tasks` must perform work in small chunks/iterations __('ticks')__ where possible, passing control back to the scheduler at appropriate points. This is known as __cooperative multi-tasking__ (so called because the tasks must cooperate by yielding control voluntarily).

The scheduler is responsible for 'ticking' the scheduled tasks, with each scheduled task being repeatedly 'ticked' until it is complete. It is up to the scheduler implementation how to do this in a way that allows all scheduled tasks to run.

A `task` can become complete in one of three ways:

    The task reaches successful completion, and optionally produces a result
    The task encounters an error and fails
    The task is cancelled by calling cancel()

When using this package, and the code you are working on contain `yield` points, these define points is where a *context switch* can happen if other tasks are pending, but will not if no other task is pending. This can also be seen as **breakpoints/traps**, like when using an debugger, when triggered, the debugger steps in, an you can view state and step thought the remainder of your code.

> A *context switch* represents the __scheduler__ yielding the flow of control from one *coroutine* to the next.

> A *coroutine* here is define as an function/method containing the `yield` keyword, in which will return *generator* object.

The **generator** object that's immediately returned, gives us access to few methods, that allow itself to progress.

So here we have a very special case with `Generators` in that it being part of the PHP language, and when looked at through the lens of how Promise's work, and that's to not block, just execute line and return. The main idea of being asynchronous.

Promises returns an object, that's placed into an event loop queue. The event loop does the actual executing the callback attached to the object. This is really a manual process, with much code overhead to manage. This is called an Reactor pattern of execution.




This performs cooperative scheduling, the basics for multitasking, asynchronous programming.

The terminology used here is more in line with [Python](https://www.python.org/dev/peps/pep-0492/) and [Curio](https://curio.readthedocs.io/en/latest/index.html#) usage. In fact, most of the source code method calls has been change to match there's.
