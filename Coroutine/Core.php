<?php

declare(strict_types=1);

use Async\Defer;
use Async\Kernel;
use Async\Channel;
use Async\Coroutine;
use Async\CoroutineInterface;
use Async\Exceptions\Panic;

if (!\function_exists('coroutine_run')) {
    /**
     * Returns a random float between two numbers.
     *
     * Works similar to Python's `random.uniform()`
     * @see https://docs.python.org/3/library/random.html#random.uniform
     *
     * @param int $min
     * @param int $max
     * @return float
     */
    function random_uniform($min, $max)
    {
        return ($min + \lcg_value() * (\abs($max - $min)));
    }

    /**
     * Return the value (in fractional seconds) of a performance counter, i.e. a clock with the highest
     * available resolution to measure a short duration. Using either `hrtime` or system's `microtime`.
     *
     * @param string $tag
     * - A reference point used to set, to get the difference between the results of consecutive calls.
     * - Will be cleared/unset on the next consecutive call.
     *
     * @return float|void
     *
     * @see https://docs.python.org/3/library/time.html#time.perf_counter
     * @see https://nodejs.org/docs/latest-v11.x/api/console.html#console_console_time_label
     */
    function timer_for(string $tag = 'perf_counter')
    {
        global $__timer__;
        if (isset($__timer__[$tag])) {
            $perf_counter = $__timer__[$tag];
            $__timer__[$tag] = null;
            unset($GLOBALS['__timer__'][$tag]);
            return (float) ($__timer__['hrtime']
                ? (\hrtime(true) / 1e+9) - $perf_counter
                : \microtime(true) - $perf_counter);
        }

        $__timer__[$tag] = (float) ($__timer__['hrtime'] ? \hrtime(true) / 1e+9 : \microtime(true));
    }

    /**
     * Makes an resolvable function from label name that's callable with `away`
     * The passed in `function/callable/task` is wrapped to be `awaitAble`
     *
     * This will create closure function in global namespace with supplied name as variable.
     *
     * @param string $labelFunction
     * @param Generator|callable $asyncFunction
     */
    function async(string $labelFunction, callable $asyncFunction)
    {
        Kernel::async($labelFunction, $asyncFunction);
    }

    /**
     * Wrap the value with `yield`, when placed within code block, it insure that
     * any *function/method* will be `awaitable` and the actual return value is properly picked up.
     *
     * use as: `return \value($value);`
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @internal
     */
    function value($value)
    {
        yield;
        return yield $value;
    }

    /**
     * Creates an object instance of the value which will signal, and insure the actual return value is properly picked up.
     *
     * use as: `return \result($value);`
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @internal
     */
    function result($value)
    {
        yield Coroutine::value($value);
    }

    /**
     * Add/schedule an `yield`-ing `function/callable/task` for background execution.
     * Will immediately return an `int`, and continue to the next instruction.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.create_task
     *
     * - This function needs to be prefixed with `yield`
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed ...$args - if **$awaitableFunction** is `Generator`, $args can hold `customState`, and `customData`
     * - for third party code integration.
     *
     * @return int $task id
     */
    function away($awaitableFunction, ...$args)
    {
        return Kernel::away($awaitableFunction, ...$args);
    }

    /**
     * Run awaitable objects in the tasks set concurrently and block until the condition specified by race.
     *
     * Controls how the `gather()` function operates.
     * `gather_wait` will behave like **Promise** functions `All`, `Some`, `Any` in JavaScript.
     *
     * @param array<int|\Generator> $tasks
     * @param int $race - If set, initiate a competitive race between multiple tasks.
     * - When amount of tasks as completed, the `gather` will return with task results.
     * - When `0` (default), will wait for all to complete.
     * @param bool $exception - If `true` (default), the first raised exception is immediately
     *  propagated to the task that awaits on gather().
     * Other awaitables in the aws sequence won't be cancelled and will continue to run.
     * - If `false`, exceptions are treated the same as successful results, and aggregated in the result list.
     * @param bool $clear - If `true` (default), close/cancel remaining results
     * @throws \LengthException - If the number of tasks less than the desired $race count.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#waiting-primitives
     *
     * @return array associative `$taskId` => `$result`
     */
    function gather_wait(array $tasks, int $race = 0, bool $exception = true, bool $clear = true)
    {
        return Kernel::gatherWait($tasks, $race, $exception, $clear);
    }

    /**
     * Run awaitable objects in the taskId sequence concurrently.
     * If any awaitable in taskId is a coroutine, it is automatically scheduled as a Task.
     *
     * If all awaitables are completed successfully, the result is an aggregate list of returned values.
     * The order of result values corresponds to the order of awaitables in taskId.
     *
     * The first raised exception is immediately propagated to the task that awaits on gather().
     * Other awaitables in the sequence won't be cancelled and will continue to run.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#asyncio.gather
     *
     * - This function needs to be prefixed with `yield`
     *
     * @param int|array $taskId
     * @return array associative `$taskId` => `$result`
     */
    function gather(...$taskId)
    {
        return Kernel::gather(...$taskId);
    }

    /**
     * Wrap the callable with `yield`, this insure the first attempt to execute will behave
     * like a generator function, will switch at least once without actually executing, return object instead.
     * - This function is used by `away()` and others, shouldn't really be called directly.
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#awaitables
     *
     * @param Generator|callable $awaitableFunction
     * @param mixed $args
     *
     * @return \Generator
     *
     * @internal
     */
    function awaitable(callable $awaitableFunction, ...$args)
    {
        return yield yield $awaitableFunction(...$args);
    }

    /**
     * Block/sleep for delay seconds.
     * Suspends the calling task, allowing other tasks to run.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#sleeping
     *
     * @param float $delay
     * @param mixed $result - If provided, it is returned to the caller when the coroutine complete
     */
    function sleep_for(float $delay = 0.0, $result = null)
    {
        return Kernel::sleepFor($delay, $result);
    }

    /**
     * Creates an communications Channel between coroutines.
     * Similar to Google Go language - basic, still needs additional functions
     * - This function needs to be prefixed with `yield`
     *
     * @return Channel $channel
     */
    function make()
    {
        return Kernel::make();
    }

    /**
     * Send message to an Channel
     * - This function needs to be prefixed with `yield`
     *
     * @param Channel $channel
     * @param mixed $message
     * @param int $taskId override send to different task, not set by `receiver()`
     */
    function sender(Channel $channel, $message = null, int $taskId = 0)
    {
        $noResult = yield Kernel::sender($channel, $message, $taskId);
        yield;
        return $noResult;
    }

    /**
     * Set task as Channel receiver, and wait to receive Channel message
     * - This function needs to be prefixed with `yield`
     *
     * @param Channel $channel
     */
    function receiver(Channel $channel)
    {
        yield Kernel::receiver($channel);
        $message = yield Kernel::receive($channel);
        return $message;
    }

    /**
     * A goroutine is a function that is capable of running concurrently with other functions.
     * To create a goroutine we use the keyword `go` followed by a function invocation
     * - This function needs to be prefixed with `yield`
     *
     * @see https://www.golang-book.com/books/intro/10#section1
     *
     * @param callable $goFunction
     * @param mixed $args - if `generator`, $args can hold `customState`, and `customData`
     *
     * @return int task id
     */
    function go($goFunction, ...$args)
    {
        return Kernel::away($goFunction, ...$args);
    }

    /**
     * Wait for the callable to complete with a timeout.
     * - This function needs to be prefixed with `yield`
     *
     * @see https://docs.python.org/3.7/library/asyncio-task.html#timeouts
     *
     * @param callable $callable
     * @param float $timeout
     */
    function wait_for($callable, float $timeout = 0.0)
    {
        return Kernel::waitFor($callable, $timeout);
    }

    /**
     * kill/remove an task using task id.
     * Optionally pass custom cancel state and error message for third party code integration.
     *
     * - This function needs to be prefixed with `yield`
     */
    function cancel_task(int $tid, $customState = null, string $errorMessage = 'Invalid task ID!')
    {
        return Kernel::cancelTask($tid, $customState, $errorMessage);
    }

    /**
     * kill/remove the current running task.
     * Optionally pass custom `cancel` state for third party code integration.
     *
     * - This function needs to be prefixed with `yield`
     */
    function kill_task($customState = null)
    {
        $currentTask = yield Kernel::getTask();
        return yield Kernel::cancelTask($currentTask, $customState);
    }

    /**
     * Performs a clean application exit and shutdown.
     * - This function needs to be prefixed with `yield`
     *
     * Provide $skipTask incase called by an Signal Handler.
     *
     * @param int $skipTask - Defaults to the main parent task.
     * - The calling `$skipTask` task id will not get cancelled, the script execution will return to.
     * - Use `getTask()` to retrieve caller's task id.
     */
    function shutdown(int $skipTask = 1)
    {
        return Kernel::shutdown($skipTask);
    }

    /**
     * Returns the current context task ID
     *
     * - This function needs to be prefixed with `yield`
     *
     * @return int
     */
    function get_task()
    {
        return Kernel::getTask();
    }

    /**
     * Set current context Task to stateless `networked`, meaning not storing any return values or exceptions on completion.
     * The task is not moved to completed task list.
     * This function will return the current context task ID.
     *
     * - This function needs to be prefixed with `yield`
     *
     * @return int
     */
    function stateless_task()
    {
        return Kernel::statelessTask();
    }

    /**
     * Wait on read stream socket to be ready read from,
     * optionally schedule current task to execute immediately/next.
     *
     * - This function needs to be prefixed with `yield`
     */
    function read_wait($stream, bool $immediately = false)
    {
        return Kernel::readWait($stream, $immediately);
    }

    /**
     * Wait on write stream socket to be ready to be written to,
     * optionally schedule current task to execute immediately/next.
     *
     * - This function needs to be prefixed with `yield`
     */
    function write_wait($stream, bool $immediately = false)
    {
        return Kernel::writeWait($stream, $immediately);
    }

    /**
     * Wait on keyboard input.
     * Will not block other task on `Linux`, will continue other tasks until `enter` key is pressed,
     * Will block on Windows, once an key is typed/pressed, will continue other tasks `ONLY` if no key is pressed.
     * - This function needs to be prefixed with `yield`
     *
     * @return string
     */
    function input_wait(int $size = 256, bool $error = false)
    {
        return Coroutine::input($size, $error);
    }

    /**
     * Return the `string` of a variable type, or does a check, compared with string of the type.
     * Types are: `callable`, `string`, `int`, `float`, `null`, `bool`, `array`, `scalar`,
     * `object`, or `resource`
     *
     * @return string|bool
     */
    function is_type($variable, string $comparedWith = null)
    {
        $checks = [
            'is_callable' => 'callable',
            'is_string' => 'string',
            'is_integer' => 'int',
            'is_float' => 'float',
            'is_null' => 'null',
            'is_bool' => 'bool',
            'is_scalar' => 'scalar',
            'is_array' => 'array',
            'is_object' => 'object',
            'is_resource' => 'resource',
        ];

        foreach ($checks as $func => $val) {
            if ($func($variable)) {
                return (empty($comparedWith)) ? $val : ($comparedWith == $val);
            }
        }

        // @codeCoverageIgnoreStart
        return 'unknown';
        // @codeCoverageIgnoreEnd
    }

    function coroutine_instance(): ?CoroutineInterface
    {
        global $__coroutine__;

        return $__coroutine__;
    }

    function coroutine_clear()
    {
        global $__coroutine__, $__timer__, $___bootstrap___, $___run___;
        if ($__coroutine__ instanceof CoroutineInterface) {
            $__coroutine__->setup(false);
            unset($GLOBALS['__coroutine__']);
        }

        $__coroutine__ = $__timer__ = $___bootstrap___ = $___run___ = null;
    }

    function coroutine_create(\Generator $routine = null)
    {
        $coroutine = \coroutine_instance();
        if (!$coroutine instanceof CoroutineInterface)
            $coroutine = new Coroutine();

        if (!empty($routine))
            $coroutine->createTask($routine);

        return $coroutine;
    }

    /**
     * This function runs the passed coroutine, taking care of managing the scheduler and
     * finalizing asynchronous generators. It should be used as a main entry point for programs, and
     * should ideally only be called once.
     *
     * @see https://docs.python.org/3.8/library/asyncio-task.html#asyncio.run
     *
     * @param Generator $routine
     */
    function coroutine_run(\Generator $routine = null)
    {
        $coroutine = \coroutine_create($routine);

        if ($coroutine instanceof CoroutineInterface) {
            $coroutine->run();
            return true;
        }
    }

    /**
     * Modeled as in `Go` Language. The behavior of defer statements is straightforward and predictable.
     * There are three simple rules:
     * 1. *A deferred function's arguments are evaluated when the defer statement is evaluated.*
     * 2. *Deferred function calls are executed in Last In First Out order after the* surrounding function returns.
     * 3. *Deferred functions can`t modify return values when is type, but can modify content of reference to array or object.*
     *
     * PHP Limitations:
     * - In this *PHP* defer implementation,
     *  you cant modify returned value. You can modify only content of returned reference.
     * - You must always set first parameter in `defer` function,
     *  the parameter MUST HAVE same variable name as other `defer`,
     *  and this variable MUST NOT exist anywhere in local scope.
     * - You can`t pass function declared in local scope by name to *defer*.
     *
     * Modified from https://github.com/tito10047/php-defer
     *
     * @see https://golang.org/doc/effective_go.html#defer
     *
     * @param Defer|null $previous defer
     * @param callable $callback
     * @param mixed ...$args
     *
     * @throws \Exception
     */
    function defer(&$previous, $callback)
    {
        $args = \func_get_args();
        \array_shift($args);
        \array_shift($args);
        Defer::deferring($previous, $callback, $args);
    }

    /**
     * Modeled as in `Go` Language. Regains control of a panicking `task`.
     *
     * Recover is only useful inside `defer()` functions. During normal execution, a call to recover will return nil
     * and have no other effect. If the current `task` is panicking, a call to recover will capture the value given
     * to panic and resume normal execution.
     *
     * @param Defer|null $previous defer
     * @param callable $callback
     * @param mixed ...$args
     */
    function recover(&$previous, $callback)
    {
        $args = \func_get_args();
        \array_shift($args);
        \array_shift($args);
        Defer::recover($previous, $callback, $args);
    }

    /**
     * Modeled as in `Go` Language.
     *
     * An general purpose function for throwing an Coroutine `Exception`,
     * or some abnormal condition needing to keep an `Task` stack trace.
     */
    function panic($message = '', $code = 0, \Throwable $previous = null)
    {
        throw new Panic($message, $code, $previous);
    }

    /**
     * An PHP Functional Programming Primitive.
     *
     * Return a curryied version of the given function. You can decide if you also
     * want to curry optional parameters or not.
     *
     * @see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#currying
     *
     * @param callable $function the function to curry
     * @param bool $required curry optional parameters ?
     * @return callable a curryied version of the given function
     */
    function curry(callable $function, $required = true)
    {
        if (\method_exists('Closure', 'fromCallable')) {
            $reflection = new \ReflectionFunction(\Closure::fromCallable($function));
        } else {
            // @codeCoverageIgnoreStart
            if (\is_string($function) && \strpos($function, '::', 1) !== false) {
                $reflection = new \ReflectionMethod($function, null);
            } elseif (\is_array($function) && \count($function) === 2) {
                $reflection = new \ReflectionMethod($function[0], $function[1]);
            } elseif (\is_object($function) && \method_exists($function, '__invoke')) {
                $reflection = new \ReflectionMethod($function, '__invoke');
            } else {
                $reflection = new \ReflectionFunction($function);
            }
            // @codeCoverageIgnoreEnd
        }
        $count = $required ?
            $reflection->getNumberOfRequiredParameters() : $reflection->getNumberOfParameters();
        return \curry_n($count, $function);
    }

    /**
     * Return a version of the given function where the $count first arguments are curryied.
     *
     * No check is made to verify that the given argument count is either too low or too high.
     * If you give a smaller number you will have an error when calling the given function. If
     * you give a higher number, arguments will simply be ignored.
     *
     * @see https://github.com/lstrojny/functional-php/blob/master/docs/functional-php.md#curry_n
     *
     * @param int $count number of arguments you want to curry
     * @param callable $function the function you want to curry
     * @return callable a curryied version of the given function
     */
    function curry_n($count, callable $function)
    {
        $accumulator = function (array $arguments) use ($count, $function, &$accumulator) {
            return function (...$newArguments) use ($count, $function, $arguments, $accumulator) {
                $arguments = \array_merge($arguments, $newArguments);
                if ($count <= \count($arguments)) {
                    return \call_user_func_array($function, $arguments);
                }
                return $accumulator($arguments);
            };
        };
        return $accumulator([]);
    }
}
