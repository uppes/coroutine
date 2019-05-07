# Coroutine

A Coroutine are special functions that is base on __generators__, with the use of `yield` and `yield from`. When used they release the flow of control back to the calling function.

This package represent that calling function, an __scheduler__, similar to an **event loop**. A coroutine needs to be scheduled to run, and once scheduled coroutines are wrapped in an `Task`s, which are a type of **Promise**.

When using this package, and the code you are working on contain `yield` points, these define points is where a *context switch* can happen if other tasks are pending, but will not if no other task is pending. This can also can be seen as breakpoints, like when using an debugger, on triggers the debugger steps in, an you can view state and step thought the remainder of your code.

> A *context switch* represents the __scheduler__ yielding the flow of control from one *coroutine* to the next.

> A *coroutine* here is define as an function/method returning an `yield` object, a *generator*.

This performs cooperative scheduling, the basics for multitasking, asynchronous programming.

The terminology used here are more in line with Python https://www.python.org/dev/peps/pep-0492/ usage.
