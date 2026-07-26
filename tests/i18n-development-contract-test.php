<?php

$root = dirname(__DIR__);

function assert_i18n_contract(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, '[i18n Contract Error] ' . $message . PHP_EOL);
        exit(1);
    }
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', $root);
}
require_once $root . '/includes/functions.php';

$registry = foxdesk_locale_registry();
assert_i18n_contract(count($registry) === 24, 'The canonical registry must contain exactly 24 product locales.');
assert_i18n_contract(isset($registry['en']), 'English must remain the fallback locale.');

$catalogFiles = glob($root . '/includes/lang/*.php') ?: [];
$catalogLocales = [];
foreach ($catalogFiles as $catalogFile) {
    $catalogLocales[] = basename($catalogFile, '.php');
}
sort($catalogLocales);

$registryLocales = array_keys($registry);
sort($registryLocales);
assert_i18n_contract(
    $catalogLocales === $registryLocales,
    'The locale registry and includes/lang catalogs must contain the same locales.'
);

foreach (['ar', 'he', 'fa', 'ur'] as $rtlLocale) {
    assert_i18n_contract(
        ($registry[$rtlLocale]['direction'] ?? null) === 'rtl',
        $rtlLocale . ' must be registered as RTL.'
    );
}
foreach (['ja', 'zh-Hans', 'zh-Hant', 'ko'] as $cjkLocale) {
    assert_i18n_contract(isset($registry[$cjkLocale]), $cjkLocale . ' must be registered.');
}

foreach (['AGENTS.md', 'CLAUDE.md'] as $instructionsFile) {
    $content = file_get_contents($root . '/' . $instructionsFile);
    assert_i18n_contract($content !== false, $instructionsFile . ' must exist.');
    assert_i18n_contract(str_contains($content, 'RTL / i18n'), $instructionsFile . ' must document RTL/i18n rules.');
    assert_i18n_contract(str_contains($content, 'bidi_isolate()'), $instructionsFile . ' must document bidi isolation.');
    assert_i18n_contract(str_contains($content, 'theme.css'), $instructionsFile . ' must document the CSS build source.');
}

$themeSource = file_get_contents($root . '/theme.css');
assert_i18n_contract($themeSource !== false, 'theme.css must exist.');
assert_i18n_contract(
    str_contains($themeSource, 'html[dir="rtl"]'),
    'theme.css must keep explicit RTL rules for directional behavior.'
);

echo 'i18n development contract OK' . PHP_EOL;
