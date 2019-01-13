<?php
namespace Async\Tests;

use Async\Task\Co;
use Async\Task\Task;
use Async\Task\Syscall;
use Async\Task\Scheduler;
use PHPUnit\Framework\TestCase;

class CoroutineCreateTest extends TestCase 
{
    /**
     * @dataProvider testNoSendProvider
     */
    public function testNoSend(
        $coroutineFactory, $expectedArray, $reindex = false
    ) {
        $coroutine = Co::routine($coroutineFactory());
        $resultArray = iterator_to_array($coroutine, !$reindex);
        $this->assertEquals($expectedArray, $resultArray);
    }

    public function testNoSendProvider() {
        return [
            // Trivial coroutine without nesting
            [
                function() {
                    for ($i = 0; $i < 5; ++$i) yield $i;
                }, [
                    0, 1, 2, 3, 4
                ]
            ],
            // Trivial coroutine with keys
            [
                function() {
                    for ($i = 0; $i < 5; ++$i) yield 5 - $i => $i;
                }, [
                    5 => 0, 4 => 1, 3 => 2, 2 => 3, 1 => 4
                ]
            ],
            // Singly-nested coroutine
            [
                function() {
                    yield "p1";
                    yield "p2";

                    yield $this->child1();

                    yield "p3";
                    yield "p4";
                }, [
                    "p1", "p2", "c1", "c2", "c3", "p3", "p4"
                ], true
            ],
            // Recursively-nested coroutine
            [
                function() {
                    yield "p1";
                    yield $this->child2(5);
                    yield "p2"; 
                }, [
                    "p1", "e5", "e4", "e3", "e2", "e1", "e0", "bot",
                    "l0", "l1", "l2", "l3", "l4", "l5", "p2"
                ], true
            ],
            // Key-preservation with simple coroutines
            [
                function() {
                    yield "pk1" => "pv1";
                    yield $this->child3();
                    yield "pk2" => "pv2";
                }, [
                    "pk1" => "pv1", "ck1" => "cv1",
                    "ck2" => "cv2", "pk2" => "pv2"
                ]
            ],
            // Co::retval() in outermost coroutine aborts
            [
                function() {
                    yield 1;
                    yield 2;
                    yield Co::retval(3);
                    yield 4;
                    yield 5;
                }, [
                    1, 2
                ]
            ],
            // retval() is accessible as yield return value
            [
                function() {
                    yield "p1";
                    $retval = (yield $this->child4());
                    yield "Retval: $retval";
                    yield "p2";
                }, [
                    "p1", "c1", "Retval: crv", "p2"
                ], true
            ],
            // plainval() does a plain passthru
            [
                function() {
                    yield (yield $this->child5());

                }, [
                    $this->child5(), Co::retval('test'), Co::plainval('test'), null
                ], true
            ],
        ];
    }

    private function child1() {
        yield "c1";
        yield "c2";
        yield "c3";
    }

    private function child2($nesting) {
        yield "e$nesting";

        if (0 === $nesting) {
            yield "bot";
        } else {
            yield $this->child2($nesting - 1);
        }

        yield "l$nesting";
    }

    private function child3() {
        yield "ck1" => "cv1";
        yield "ck2" => "cv2";
    }

    private function child4() {
        yield "c1";
        yield Co::retval("crv");
        yield "c2";
    }

    private function child5() {
        yield Co::plainval($this->child5());
        yield Co::plainval(Co::retval('test'));
        yield Co::plainval(Co::plainval('test'));
    }


    /**
     * @dataProvider testSendProvider
     */
    public function testSend($coroutineFactory, $sendValues, $expectedArray) {
        $coroutine = Co::routine($coroutineFactory());
        $resultArray = [$coroutine->current()];
        foreach ($sendValues as $value) {
            $resultArray[] = $coroutine->send($value);
        }
        $this->assertEquals($expectedArray, $resultArray);
    }

    public function testSendProvider() {
        return [
            // Coroutine without nesting
            [
                function() {
                    yield (yield (yield (yield (yield 'a'))));
                }, [
                    'b', 'c', 'd', 'e'
                ], [
                    'a', 'b', 'c', 'd', 'e'
                ]
            ],
            // Coroutine with nesting
            [
                function() {
                    $s = (yield 'a');
                    $s = (yield $this->child6($s));
                    yield $s;
                }, [
                    'b', 'c', 'd', 'e'
                ], [
                    'a', 'b', 'c', 'd', 'e'
                ]
            ],
        ];
    }

    private function child6($s) {
        yield Co::retval(yield (yield (yield $s)));
    }

    /**
     * @dataProvider testThrowProvider
     */
    public function testThrow(
        $coroutineFactory, $throwAtKeys, $expectedArray, $shouldThrow
    ) {
        $coroutine = Co::routine($coroutineFactory());
        $throwAtKeys = array_flip($throwAtKeys);

        $hasThrown = false;
        $resultArray = [];
        try {
            foreach ($coroutine as $key => $value) {
                $resultArray[] = $value;

                if (isset($throwAtKeys[$key])) {
                    $resultArray[] = $coroutine->throw(new \Exception);
                }
            }
        } catch (\Exception $e) {
            $hasThrown = true;
        }

        if ($shouldThrow !== $hasThrown) {
            $this->fail('Throwiness does not match');
        }

        $this->assertEquals($expectedArray, $resultArray);
    }

    public function testThrowProvider() {
        return [
            // Throwing an exception from the outermost coroutine
            [
                function() {
                    yield 'a';
                    throw new \Exception;
                    yield 'b';
                }, [], ['a'], true
            ],
            // Throwing an exception into the outermost coroutine (uncaught)
            [
                function() {
                    yield 'a';
                    yield 'throw' => 'b';
                    yield 'c';
                }, ['throw'], ['a', 'b'], true
            ],
            // Throwing an exception into the outermost coroutine (caught)
            [
                function() {
                    yield 'a';
                    try {
                        yield 'throw' => 'b';
                    } catch (\Exception $e) { yield 'e'; }
                    yield 'c';
                }, ['throw'], ['a', 'b', 'e', 'c'], false
            ],
            // Throwing an exception into a nested coroutine (uncaught)
            [
                function() {
                    yield 'a';
                    yield $this->child7();
                    yield 'b';
                }, ['throw'], ['a', 'c'], true
            ],
            // Throwing an exception into a nested coroutine (caught)
            [
                function() {
                    yield 'a';
                    yield $this->child8();
                    yield 'b';
                }, ['throw'], ['a', 'c', 'e', 'b'], false
            ],
            // Throwing an exception into a nested coroutine (caught in outer)
            [
                function() {
                    yield 'a';
                    try {
                        yield $this->child7();
                    } catch (\Exception $e) { yield 'e'; }
                    yield 'b';
                }, ['throw'], ['a', 'c', 'e', 'b'], false
            ],
            // Caught exception in deeply nested coroutine (throw() throws)
            [
                function() {
                    yield 'a';
                    try {
                        yield $this->child9();
                    } catch (\Exception $e) { yield 'e'; }
                    yield 'b';
                }, ['throw'], ['a', 'c', 'e', 'b'], false
            ],
            // Exception during retval sending to outermost coroutine
            [
                function() {
                    yield 'a';
                    yield $this->child10();
                    throw new \Exception;
                    yield 'b';
                }, [], ['a'], true
            ],
            // Exception during retval sending in inner coroutine
            [
                function() {
                    yield 'a';
                    try {
                        yield $this->child11();
                    } catch (\Exception $e) { yield 'e'; }
                    yield 'b';
                }, [], ['a', 'e', 'b'], false
            ],
        ];
    }

    private function child7() {
        yield 'throw' => 'c';
    }

    private function child8() {
        try {
            yield 'throw' => 'c';
        } catch (\Exception $e) { yield 'e'; }
    }

    private function child9() {
        yield $this->child7();
    }

    private function child10() {
        yield Co::retval('foo');
    }

    private function child11() {
        yield $this->child10();
        throw new \Exception;
    }
}
