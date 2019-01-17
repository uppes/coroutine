[![Codacy Badge](https://api.codacy.com/project/badge/Grade/fbd1d327f0d14164833396e2fbdf492b)](https://app.codacy.com/app/techno-express/coroutine?utm_source=github.com&utm_medium=referral&utm_content=uppes/coroutine&utm_campaign=Badge_Grade_Dashboard)
[![Build Status](https://travis-ci.org/uppes/coroutine.svg?branch=master)](https://travis-ci.org/uppes/coroutine)[![codecov](https://codecov.io/gh/uppes/coroutine/branch/master/graph/badge.svg)](https://codecov.io/gh/uppes/coroutine)

Coroutine - PHP multitasking
========================================

This project implements a cooperative task scheduler using yield & generators for coroutines.

The implementation is described in [this blog post][blog_post]. This is an experimental coroutine task library for PHP.

It allows developers to use the new yield keyword in PHP 5.5 and higher to asynchronously wait for the result of a task (similar to the await keyword in C#), allowing for asynchronous code that reads very much like the equivalent synchronous code:

```php
<?php

// Wait for $task to complete and receive its result, but without blocking execution
$taskResult = ( yield $task );

// Use $taskResult...
```

This is of particular benefit when combined with a non-blocking I/O library.

Concepts
===

The main concept in Async is that of a task. A task is an object that represents some work to be done, potentially with a result at the end of it. These tasks are registered with a scheduler that is responsible for running them.

Due to the single-threaded nature of PHP (without extensions anyway), we cannot think of a task as doing a single long-running calculation - this will block the single thread until the task is finished. Instead, tasks must perform work in small chunks ('ticks') where possible, passing control back to the scheduler at appropriate points. This is known as cooperative multi-tasking (so called because the tasks must cooperate by yielding control voluntarily).

The scheduler is responsible for 'ticking' the scheduled tasks, with each scheduled task being repeatedly 'ticked' until it is complete. It is up to the scheduler implementation how to do this in a way that allows all scheduled tasks to run. The scheduler exits when all scheduled tasks are complete (or when it is manually stopped).

A task can become complete in one of three ways:

    The task reaches successful completion, and optionally produces a result
    The task encounters an error and fails
    The task is cancelled by calling cancel()

In Async, any object implementing the TaskInterface can be used as a task.
___

There are several built-in tasks - please see the source for more detail:

`CallableTask`
> Task that takes a callable object. When the task is 'ticked', the callable object is executed and its return value becomes the task result. This task is complete after a single tick.

`RecurringTask`
> Task that takes a callable object and an optional number of times to execute the callable object. Each time the task is 'ticked', the callable object is executed until it has been called enough times (if the number of times is not given, the callable object is executed on each scheduler tick forever, and the task only completes if an error occurs or it is cancelled).

`PromiseTask`
> Task that takes a React promise. The task waits for the promise to be resolved, at which point the task is complete and its result is the value the promise was resolved with.

`GeneratorTask`
> This is the task that enables the yield syntax introduced above. Takes a Generator that yields Tasks. Each time the generator task is 'ticked', it checks to see if the currently yielded task is complete. If it is, then it resumes the generator, passing the result of the task back in. The generator task itself is complete when the generator yields no more tasks.

`SomeTask`
> Task that takes an array of tasks and a number of tasks required. It is considered to have completed successfully when the required number of tasks have completed successfully, and to have failed when it is not possible for the required number of tasks to complete successfully. If it succeeds, the result is an array of the results of the completed tasks. If it fails, the exception is a compound exception containing the individual exceptions that caused the failures.

`AllTask`
> Special case of a SomeTask that is considered to have completed successfully only if all the tasks complete successfully.

`AnyTask`
> Special case of a SomeTask that is considered to have completed successfully once one of the tasks completes successfully. Rather than an array, the result is the result of the first successful task.

`DelayedTask`
> Takes a task to wrap and a delay in seconds and schedules the wrapped task with the given delay when first ticked. The delayed task completes when the wrapped task completes, and takes its result/failure/cancellation state from the wrapped task.

`ThrottledTask`
> Takes a task to wrap and an interval in seconds and schedules the wrapped task with the given tick interval when first ticked. The throttled task completes when the wrapped task completes, and takes its result/failure/cancellation state from the wrapped task.

Utility functions

The class __Co__ provides several static utility functions for easily creating certain kinds of tasks.

`Co::async(mixed $object)`

    Takes any object and returns a suitable task for that object:

        If given a task, it returns that task.
        If given a promise, it returns a PromiseTask for that promise.
        If given a generator, it returns a GeneratorTask for that generator.
        If given a callable object, it returns a CallableTask for the callable.
        If given any other object, it returns a task whose result will be the given object.

`Co::some(array $tasks, integer $howMany)`

    Returns a SomeTask for the given tasks that requires $howMany of those tasks to complete.
	
`Co::all(array $tasks)`

    Returns an AllTask for the given tasks that requires all of those tasks to complete.
	
`Co::any(array $tasks)`

    Returns an AnyTask for the given tasks that requires one of those tasks to complete.
	
`Co::delay(mixed $object, float $delay)`

    Creates a task for the given object using Co::async and returns a DelayedTask that delays the execution of that task by $delay seconds. $delay can have a fractional part, e.g. for a half second delay, specify $delay = 0.5.
	
`Co::throttle(mixed $object, float $tickInterval)`

    Creates a task for the given object using Co::async and returns a ThrottledTask that throttles the execution of the tick method of that task by $tickInterval seconds. $tickInterval can have a fractional part, e.g. for a half second interval, specify $tickInterval = 0.5.

	
  [blog_post]: http://nikic.github.com/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html