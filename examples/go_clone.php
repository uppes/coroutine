<?php
/**
 * @see https://golangbot.com/goroutines/
 * @see https://play.golang.org/p/oltn5nw0w3
 */
include 'vendor/autoload.php';

function numbers() {
	for ($i = 1; $i <= 5; $i++) {
		yield \sleep_for(250 * \MILLISECOND);
		print(' '.$i);
	}
}

function alphabets() {
	for ($i = 'a'; $i <= 'e'; $i++) {
		yield \sleep_for(400 * \MILLISECOND);
		print(' '.$i);
	}
}

function main() {
	yield \go('numbers');
	yield \go('alphabets');
	yield \sleep_for(3000 * \MILLISECOND);
	print(" main terminated");
}

\coroutine_run(\main());
