<?php

$root = dirname(__DIR__);

function assert_rtl(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, '[RTL Contract Error] ' . $message . PHP_EOL);
        exit(1);
    }
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', $root);
}
require_once $root . '/includes/functions.php';

assert_rtl(function_exists('is_rtl'), 'is_rtl() function must exist in functions.php');
assert_rtl(function_exists('get_app_direction'), 'get_app_direction() function must exist in functions.php');
assert_rtl(function_exists('get_supported_languages'), 'get_supported_languages() function must exist in functions.php');

// Test RTL helper logic
assert_rtl(is_rtl('ar') === true, 'is_rtl("ar") must return true');
assert_rtl(is_rtl('he') === true, 'is_rtl("he") must return true');
assert_rtl(is_rtl('fa') === true, 'is_rtl("fa") must return true');
assert_rtl(is_rtl('ur') === true, 'is_rtl("ur") must return true');
assert_rtl(is_rtl('en') === false, 'is_rtl("en") must return false');
assert_rtl(is_rtl('cs') === false, 'is_rtl("cs") must return false');

assert_rtl(get_app_direction('ar') === 'rtl', 'get_app_direction("ar") must return "rtl"');
assert_rtl(get_app_direction('en') === 'ltr', 'get_app_direction("en") must return "ltr"');

$supported = get_supported_languages();
assert_rtl(isset($supported['ar']), 'Supported languages must include Arabic ("ar")');
assert_rtl($supported['ar']['rtl'] === true, 'Arabic language metadata must specify rtl = true');

// 2. Test Translations Loader
$translations = include $root . '/includes/translations.php';
assert_rtl(isset($translations['ar']), 'includes/translations.php must load Arabic translations');
assert_rtl(is_array($translations['ar']), 'Arabic translations must be an array');
assert_rtl(isset($translations['ar']['Dashboard']), 'Arabic translations must translate "Dashboard"');

// 3. Test App Shell Integration
if (!function_exists('is_admin')) {
    function is_admin(): bool { return true; }
}
require_once $root . '/includes/modules/app/app-shell.php';
$dummy_user = ['id' => 1, 'first_name' => 'Test', 'last_name' => 'User', 'email' => 'test@example.com', 'role' => 'admin'];
$shell_user = app_shell_user($dummy_user);
assert_rtl(array_key_exists('dir', $shell_user), 'app_shell_user payload must contain "dir" property');
assert_rtl(array_key_exists('is_rtl', $shell_user), 'app_shell_user payload must contain "is_rtl" property');

// 4. Test HTML Template Declarations
$templates = [
    'includes/header.php',
    'pages/login.php',
    'pages/forgot-password.php',
    'pages/reset-password.php',
    'pages/ticket-share.php',
    'pages/report-share.php',
    'pages/report-public.php',
    'install.php',
];

foreach ($templates as $file) {
    $content = file_get_contents($root . '/' . $file);
    assert_rtl($content !== false, 'Unable to read ' . $file);
    assert_rtl(
        str_contains($content, 'dir=') || str_contains($content, 'get_app_direction'),
        'HTML template must bind dir attribute or get_app_direction(): ' . $file
    );
}

// 5. Test CSS Theme RTL Rules
$theme_css = file_get_contents($root . '/theme.css');
assert_rtl($theme_css !== false, 'Unable to read theme.css');
assert_rtl(str_contains($theme_css, 'html[dir="rtl"]'), 'theme.css must contain html[dir="rtl"] selectors');
assert_rtl(str_contains($theme_css, 'direction: rtl;'), 'theme.css must set direction: rtl');
assert_rtl(str_contains($theme_css, 'margin-inline-start: var(--app-sidebar-width);'), 'theme.css must use logical main-content spacing');
assert_rtl(str_contains($theme_css, 'html[dir="rtl"] .sidebar.open'), 'RTL mobile sidebar must follow the actual .open state class');
assert_rtl(str_contains($theme_css, '.header-search-icon'), 'theme.css must mirror the shared header search component');
assert_rtl(str_contains($theme_css, 'inset-inline-end: 1rem;'), 'theme.css must use logical popover positioning');
assert_rtl(str_contains($theme_css, 'html[dir="rtl"] .text-left'), 'RTL must mirror legacy text alignment utilities globally');
assert_rtl(str_contains($theme_css, 'text-align: start;'), 'Shared table styles must use logical text alignment');
assert_rtl(str_contains($theme_css, '.app-toast-fallback'), 'Fallback toasts must use shared logical positioning');

$app_header_js = file_get_contents($root . '/assets/js/app-header.js');
assert_rtl($app_header_js !== false, 'Unable to read app-header.js');
assert_rtl(!str_contains($app_header_js, 'style.marginLeft'), 'Sidebar compact mode must not hard-code a physical main-content margin');

echo 'RTL support contract OK' . PHP_EOL;
