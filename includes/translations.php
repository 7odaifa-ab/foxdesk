<?php

if (!function_exists('foxdesk_locale_registry')) {
    require_once __DIR__ . '/locale-functions.php';
}

$activeLocale = function_exists('get_app_language')
    ? get_app_language()
    : (normalize_locale_tag($_SESSION['lang'] ?? 'en') ?? 'en');
$translations = [];
foreach (array_values(array_unique([$activeLocale, 'en'])) as $locale) {
    $translations[$locale] = foxdesk_translation_catalog($locale);
}

return $translations;
