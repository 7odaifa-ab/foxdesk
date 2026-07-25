<?php
define('BASE_PATH', dirname(__DIR__));

function get_supported_languages(): array
{
    return ['en' => ['rtl' => false], 'cs' => ['rtl' => false], 'de' => ['rtl' => false], 'it' => ['rtl' => false], 'es' => ['rtl' => false], 'ar' => ['rtl' => true]];
}

function schema_require(): void {}
function db_fetch_one(): ?array { return null; }

require_once BASE_PATH . '/includes/mailer.php';
require_once BASE_PATH . '/includes/modules/email/email-renderer.php';

function assert_arabic_email(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$keys = [
    'status_change',
    'new_comment',
    'new_ticket',
    'password_reset',
    'ticket_confirmation',
    'ticket_assignment',
    'recurring_task_assignment',
    'long_timer_alert',
    'welcome_email',
];

foreach ($keys as $key) {
    $template = get_builtin_email_template($key, 'ar');
    assert_arabic_email(is_array($template), "Missing Arabic built-in template: {$key}");
    assert_arabic_email($template['language'] === 'ar', "Arabic template returned the wrong language: {$key}");
    assert_arabic_email((bool) preg_match('/[\x{0600}-\x{06FF}]/u', $template['subject'] . $template['body']), "Arabic template has no Arabic copy: {$key}");
}

assert_arabic_email(normalize_email_template_language('ar') === 'ar', 'Arabic email language must be accepted.');
assert_arabic_email(get_email_template_phrase('not_provided', 'ar') === 'غير محدد', 'Arabic fallback phrase is missing.');

$html = foxdesk_render_ticket_email_html([
    'language' => 'ar',
    'title' => 'تم تحديث التذكرة',
    'body' => "مرحباً\n\n- عنصر أول\n- عنصر ثانٍ",
]);
assert_arabic_email(strpos($html, '<html lang="ar" dir="rtl">') !== false, 'Arabic email HTML must declare language and direction.');
assert_arabic_email(strpos($html, 'direction:rtl') !== false, 'Arabic email HTML must set RTL direction.');
assert_arabic_email(strpos($html, 'margin:0 20px 14px 0') !== false, 'Arabic email lists must use RTL spacing.');

echo "Arabic email contract OK\n";