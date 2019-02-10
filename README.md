[![Build Status](https://travis-ci.org/uppes/coroutine.svg?branch=master)](https://travis-ci.org/uppes/coroutine)[![codecov](https://codecov.io/gh/uppes/coroutine/branch/master/graph/badge.svg)](https://codecov.io/gh/uppes/coroutine)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/fbd1d327f0d14164833396e2fbdf492b)](https://app.codacy.com/app/techno-express/coroutine?utm_source=github.com&utm_medium=referral&utm_content=uppes/coroutine&utm_campaign=Badge_Grade_Dashboard)

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

[Curious Course on Coroutines and Concurrency](https://youtu.be/Z_OAlIhXziw) __video__

[Cooperative multitasking with generators](https://youtu.be/cY8FUhZvK7w) __video__


  [blog_post]: http://nikic.github.com/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html
