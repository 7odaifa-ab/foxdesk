<?php

$root = dirname(__DIR__);
$helper = (string) file_get_contents($root . '/includes/admin-crud-helper.php');
$router = (string) file_get_contents($root . '/includes/api/router.php');

$assertions = [
    'JSON input validates UTF-8' => str_contains($helper, "mb_check_encoding(\$raw, 'UTF-8')")
        && str_contains($helper, 'JSON_THROW_ON_ERROR'),
    'Request parameters are recursively validated' => str_contains($helper, 'function api_validate_utf8_values(array $values): void')
        && str_contains($helper, 'api_validate_utf8_values($candidate);'),
    'Router validates GET and POST before dispatch' => str_contains($router, 'api_validate_utf8_values($_GET);')
        && str_contains($router, 'api_validate_utf8_values($_POST);'),
];

$failed = array_keys(array_filter($assertions, static fn(bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, "API UTF-8 boundary contract failed:\n - " . implode("\n - ", $failed) . "\n");
    exit(1);
}

echo "API UTF-8 boundary contract passed.\n";
