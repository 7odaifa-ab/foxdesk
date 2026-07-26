<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/locale-functions.php';

function assert_locale_contract($condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$expected = [
    'en', 'cs', 'de', 'es', 'it', 'ar',
    'fr', 'pt-BR', 'pt-PT', 'pl', 'nl', 'tr',
    'ru', 'uk', 'ja', 'zh-Hans', 'zh-Hant', 'ko',
    'he', 'fa', 'ur', 'hi', 'id', 'vi',
];
$registry = foxdesk_locale_registry();

assert_locale_contract(
    array_keys($registry) === $expected,
    'The public locale registry must contain the planned 24 locales in release-wave order.'
);

foreach ($registry as $tag => $metadata) {
    foreach (['slug', 'englishName', 'nativeName', 'direction', 'script', 'channels'] as $field) {
        assert_locale_contract(!empty($metadata[$field]), "{$tag} is missing locale metadata: {$field}");
    }
    assert_locale_contract(
        foxdesk_canonical_locale($tag) === $tag,
        "{$tag} does not round-trip through BCP-47 canonicalization."
    );
    assert_locale_contract(
        $tag === 'en' ? $metadata['fallback'] === null : $metadata['fallback'] === 'en',
        "{$tag} must fall back directly to English."
    );
    foreach (['self_hosted', 'saas', 'ios', 'website'] as $channel) {
        assert_locale_contract(
            in_array($metadata['channels'][$channel] ?? null, ['draft', 'beta', 'stable'], true),
            "{$tag} has no valid {$channel} release state."
        );
    }
}

assert_locale_contract(
    array_keys(array_filter($registry, static fn(array $locale): bool => $locale['direction'] === 'rtl'))
        === ['ar', 'he', 'fa', 'ur'],
    'Only Arabic, Hebrew, Persian and Urdu may default to RTL.'
);
foreach (['ja', 'zh-Hans', 'zh-Hant', 'ko'] as $tag) {
    assert_locale_contract($registry[$tag]['direction'] === 'ltr', "{$tag} must use horizontal LTR application layout.");
}

assert_locale_contract(foxdesk_canonical_locale('pt_br') === 'pt-BR', 'pt_BR alias is not canonicalized.');
assert_locale_contract(foxdesk_canonical_locale('zh-CN') === 'zh-Hans', 'zh-CN must map to zh-Hans.');
assert_locale_contract(foxdesk_canonical_locale('zh-TW') === 'zh-Hant', 'zh-TW must map to zh-Hant.');
assert_locale_contract(
    foxdesk_negotiate_locale('fr-CA;q=0.8, de-DE;q=0.9', 'self_hosted', true) === 'de',
    'Accept-Language quality and base-language fallback are not respected.'
);
assert_locale_contract(
    foxdesk_negotiate_locale('zh-TW, zh-CN;q=0.8', 'self_hosted', true) === 'zh-Hant',
    'Chinese script negotiation must preserve the requested script.'
);

assert_locale_contract(foxdesk_locale_status('ar', 'self_hosted') === 'beta', 'Arabic self-hosted state must remain beta until QA.');
assert_locale_contract(foxdesk_locale_status('fr', 'self_hosted') === 'draft', 'Unreviewed French must remain draft.');
assert_locale_contract(!isset(foxdesk_available_locales('self_hosted', false)['fr']), 'Draft locales must be hidden by default.');
assert_locale_contract(isset(foxdesk_available_locales('self_hosted', true)['fr']), 'Draft locales must be available to explicit QA builds.');

$pluralExpectations = [
    ['ar', 0, 'zero'],
    ['ar', 2, 'two'],
    ['ar', 7, 'few'],
    ['ar', 20, 'many'],
    ['cs', 3, 'few'],
    ['cs', 1.5, 'many'],
    ['pl', 22, 'few'],
    ['ru', 25, 'many'],
    ['fa', 0.5, 'one'],
    ['pt-PT', 0, 'other'],
    ['ja', 1, 'other'],
    ['zh-Hans', 2, 'other'],
];
foreach ($pluralExpectations as [$locale, $number, $expectedCategory]) {
    assert_locale_contract(
        foxdesk_plural_category($locale, $number) === $expectedCategory,
        "Unexpected {$locale} plural category for {$number}."
    );
}

$spoofed = "invoice\u{202E}gpj.exe\u{2066}";
assert_locale_contract(
    foxdesk_strip_bidi_controls($spoofed) === 'invoicegpj.exe',
    'Bidi override and isolate controls must be removed from identifiers.'
);

assert_locale_contract(foxdesk_canonical_locale('en-xa') === 'en-XA', 'Expanded pseudolocale must canonicalize.');
assert_locale_contract(foxdesk_canonical_locale('ar_xb') === 'ar-XB', 'RTL pseudolocale must canonicalize.');
assert_locale_contract(normalize_locale_tag('en-XA') === null, 'Pseudolocales must be inaccessible outside explicit QA builds.');
putenv('FOXDESK_ENABLE_PSEUDO_LOCALES=1');
assert_locale_contract(normalize_locale_tag('en-XA') === 'en-XA', 'QA build cannot activate en-XA.');
assert_locale_contract(get_app_direction('ar-XB') === 'rtl', 'ar-XB must exercise RTL layout.');
$pseudoMessage = foxdesk_pseudolocalize('Welcome, {name}!', 'en-XA');
assert_locale_contract($pseudoMessage !== 'Welcome, {name}!', 'en-XA must visibly expand source copy.');
assert_locale_contract(strpos($pseudoMessage, '{name}') !== false, 'Pseudolocalization must preserve placeholders.');
assert_locale_contract(isset(get_supported_languages()['ar-XB']), 'QA language selector must expose ar-XB.');
putenv('FOXDESK_ENABLE_PSEUDO_LOCALES');

echo "Locale registry contract tests passed\n";
