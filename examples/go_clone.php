<?php
/**
 * @see https://golangbot.com/goroutines/
 * @see https://play.golang.org/p/oltn5nw0w3
 */
include 'vendor/autoload.php';

function numbers() {
	for ($i = 1; $i <= 5; $i++) {
		yield \go_sleep(250 * \MILLISECOND);
		print(' '.$i);
	}
}

function alphabets() {
	for ($i = 'a'; $i <= 'e'; $i++) {
		yield \go_sleep(400 * \MILLISECOND);
		print(' '.$i);
	}
}

function main() {
	yield \go('numbers');
	yield \go('alphabets');
	yield \go_sleep(3000 * \MILLISECOND);
	print(" main terminated");
}

\coroutine_run(\main());
