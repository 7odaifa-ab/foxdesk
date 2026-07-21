<?php

$root = dirname(__DIR__);
$page = file_get_contents($root . '/pages/ticket-detail.php');
$content = file_get_contents($root . '/includes/components/ticket-detail-content.php');
$composer = file_get_contents($root . '/includes/components/ticket-detail-composer.php');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert($page !== false && $content !== false && $composer !== false, 'Ticket composer surface files must be readable.');
$assert(str_contains($page, "/includes/components/ticket-detail-content.php"), 'Ticket detail route must delegate its content surface.');
$assert(str_contains($content, "/includes/components/ticket-detail-composer.php"), 'Ticket detail content must include the composer component.');
$assert(!str_contains($page, 'data-ticket-composer-surface'), 'Ticket composer markup must stay inside the composer component.');
$assert(!str_contains($content, 'data-ticket-composer-surface'), 'Ticket composer markup must not move into the content composition file.');

foreach ([
    'data-ticket-composer-surface',
    'id="comment-form"',
    'id="comment-editor"',
    'id="internal-editor"',
    'name="status_id"',
    'id="comment-upload-zone"',
    'id="comment-file-input"',
    'id="manual-entry-row"',
    'id="timer-controls"',
    'id="agent-cc-dropdown-container"',
    'id="comment-submit-btn"',
] as $needle) {
    $assert(str_contains($composer, $needle), 'Ticket composer component missing: ' . $needle);
}

foreach ([
    'ticket_detail_visible_comments',
    'ticket_detail_visible_attachments',
    'ticket_detail_build_timeline',
] as $forbidden) {
    $assert(!str_contains($composer, $forbidden), 'Composer component must not own read model logic: ' . $forbidden);
}

echo "Ticket composer surface contract OK\n";
