<?php

if (!defined('SECRET_KEY')) {
    define('SECRET_KEY', 'agent-work-log-planner-contract-secret');
}
function foxdesk_normalize_backdated_datetime_input($value)
{
    $timestamp = strtotime(trim((string) $value));
    return $timestamp === false ? false : date('Y-m-d H:i:s', $timestamp);
}
require_once dirname(__DIR__) . '/includes/modules/agent/work-log-planner.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};
$expectInvalid = static function (callable $callback, string $message) use ($assert): void {
    try {
        $callback();
        $assert(false, $message);
    } catch (InvalidArgumentException $e) {
        $assert($e->getMessage() !== '', 'Validation errors must explain the rejected plan.');
    }
};
$user = ['id' => 12, 'tenant_id' => 7];
$validInput = [
    'structure' => 'one_ticket',
    'allocation_basis' => 'approved_total',
    'total_minutes' => 278,
    'tickets' => [[
        'title' => 'Úpravy produktové stránky',
        'description' => 'Návrh a realizace vizuálních úprav produktové stránky.',
        'entries' => [
            ['worked_on' => '2026-07-21', 'content' => 'Upravená struktura hero sekce a hlavní vizuální hierarchie.', 'duration_minutes' => 83, 'time_precision' => 'allocated'],
            ['worked_on' => '2026-07-22', 'content' => 'Doplněné responzivní varianty produktových karet a kontaktního bloku.', 'duration_minutes' => 107, 'time_precision' => 'allocated'],
            ['worked_on' => '2026-07-23', 'content' => 'Doladěné rozestupy, štítky a finální kontrola na mobilu.', 'duration_minutes' => 88, 'time_precision' => 'allocated'],
        ],
    ]],
];
$plan = foxdesk_agent_normalize_work_log_plan($validInput, $user);
$preview = foxdesk_agent_work_log_preview($plan);
$assert($plan['total_minutes'] === 278, 'Plan must preserve the approved exact total.');
$assert($plan['tickets'][0]['entries'][0]['worked_on'] === '2026-07-21', 'Work date must stay in worked_on.');
$assert($plan['tickets'][0]['entries'][0]['started_at'] === null, 'Allocated entries must not invent a clock time.');
$assert($preview['tickets'][0]['ticket_total_minutes'] === 278, 'Preview must expose the complete ticket total.');
$assert($preview['requires_user_confirmation'] === true, 'Preview must require explicit confirmation.');
$assert(str_ends_with($plan['expires_at'], 'Z'), 'Plan expiry must include an explicit UTC timezone.');
$assert(strtotime($plan['expires_at']) > time(), 'A newly prepared plan must not be considered expired.');
$assert(foxdesk_agent_plan_signature($plan, $user) === foxdesk_agent_plan_signature($plan, $user), 'Plan signature must be stable.');

$withDate = $validInput;
$withDate['tickets'][0]['description'] .= ' Dokončeno 23. 7. 2026.';
$expectInvalid(static fn() => foxdesk_agent_normalize_work_log_plan($withDate, $user), 'Dates in ticket descriptions must be rejected.');
$withDuration = $validInput;
$withDuration['tickets'][0]['entries'][0]['content'] .= ' Práce zabrala 83 minut.';
$expectInvalid(static fn() => foxdesk_agent_normalize_work_log_plan($withDuration, $user), 'Durations in comments must be rejected.');
$rounded = $validInput;
$rounded['total_minutes'] = 180;
foreach ($rounded['tickets'][0]['entries'] as &$entry) {
    $entry['duration_minutes'] = 60;
}
unset($entry);
$expectInvalid(static fn() => foxdesk_agent_normalize_work_log_plan($rounded, $user), 'Repeated rounded allocations must be rejected.');
$overrideMissingReason = $validInput;
$overrideMissingReason['allow_temporal_text'] = true;
$expectInvalid(static fn() => foxdesk_agent_normalize_work_log_plan($overrideMissingReason, $user), 'Override needs a reason.');
$withDate['allow_temporal_text'] = true;
$withDate['temporal_text_reason'] = 'User explicitly requested a date.';
$assert(foxdesk_agent_normalize_work_log_plan($withDate, $user)['allow_temporal_text'] === true, 'Explicit override must work.');

$exact = [
    'structure' => 'one_ticket',
    'allocation_basis' => 'actual',
    'total_minutes' => 47,
    'tickets' => [[
        'title' => 'Finální vizuální opravy',
        'description' => 'Ověření a doladění posledních detailů.',
        'entries' => [[
            'content' => 'Dokončené vizuální opravy a ověření zobrazení.',
            'worked_on' => '2026-07-25',
            'duration_minutes' => 47,
            'time_precision' => 'exact',
            'started_at' => '2026-07-25 09:00:00',
            'ended_at' => '2026-07-25 09:47:00',
        ]],
    ]],
];
$exactPlan = foxdesk_agent_normalize_work_log_plan($exact, $user);
$assert($exactPlan['tickets'][0]['entries'][0]['started_at'] === '2026-07-25 09:00:00', 'Exact times must be preserved.');
$exact['tickets'][0]['entries'][0]['duration_minutes'] = 46;
$exact['total_minutes'] = 46;
$expectInvalid(static fn() => foxdesk_agent_normalize_work_log_plan($exact, $user), 'Exact duration must match timestamps.');

echo "Agent work-log planner OK\n";
