<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/functions.php';

function assert_plural_runtime($condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$_GET = [];
$_COOKIE = [];
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';

$_SESSION['lang'] = 'en';
assert_plural_runtime(tn('comment.count', 1) === '1 comment', 'English singular did not use _one.');
assert_plural_runtime(tn('comment.count', 5) === '5 comments', 'English plural did not use _other.');

$_SESSION['lang'] = 'cs';
assert_plural_runtime(tn('comment.count', 1) === '1 komentář', 'Czech singular did not use _one.');
assert_plural_runtime(tn('comment.count', 3) === '3 komentáře', 'Czech few form was not selected.');
assert_plural_runtime(tn('comment.count', 7) === '7 komentářů', 'Czech other form was not selected.');

$_SESSION['lang'] = 'ar';
assert_plural_runtime(tn('comment.count', 0) === '0 تعليق', 'Arabic zero form was not selected.');
assert_plural_runtime(tn('comment.count', 2) === '2 تعليقان', 'Arabic dual form was not selected.');
assert_plural_runtime(tn('comment.count', 8) === '8 تعليقات', 'Arabic few form was not selected.');

echo "Plural runtime contract tests passed\n";
