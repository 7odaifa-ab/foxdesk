<?php

$root = dirname(__DIR__);
$builder = (string) file_get_contents($root . '/scripts/build-update-package.sh');
$updater = (string) file_get_contents($root . '/includes/update-functions.php');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(
    str_contains($builder, 'assets bin includes locales pages migrations'),
    'Release ZIP must include canonical locale registry and catalogs.'
);
$assert(
    str_contains($updater, "'assets', 'bin', 'includes', 'locales', 'migrations', 'pages'"),
    'Backup/rollback scope must include localization runtime and migrations.'
);
$assert(is_file($root . '/locales/registry.json'), 'Locale registry is missing from release source.');
foreach (['inter-cyrillic-400.woff2', 'noto-sans-arabic-400.woff2', 'noto-sans-hebrew-400.woff2', 'noto-nastaliq-urdu-400.woff2', 'noto-sans-devanagari-400.woff2'] as $font) {
    $assert(is_file($root . '/assets/fonts/' . $font), "Release font subset is missing: {$font}");
}

echo "i18n release package contract tests passed\n";
