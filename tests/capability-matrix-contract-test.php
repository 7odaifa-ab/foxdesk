<?php
$root = dirname(__DIR__);
$path = $root . '/docs/qa/capability-matrix.json';
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$assert(is_file($path), 'Capability matrix is missing.');
$matrix = json_decode((string) file_get_contents($path), true);
$assert(is_array($matrix), 'Capability matrix must be valid JSON.');
foreach (['tickets', 'comments', 'time', 'attachments', 'clients', 'reports', 'notifications', 'api_tokens'] as $resource) {
    $assert(isset($matrix['resources'][$resource]) && is_array($matrix['resources'][$resource]), "Missing {$resource} capability definition.");
}
foreach (($matrix['resources'] ?? []) as $resource => $operations) {
    foreach ($operations as $operation => $capability) {
        $assert(!empty($capability['roles']) && is_array($capability['roles']), "{$resource}.{$operation} must define roles.");
        foreach (['web', 'api', 'ios'] as $surface) {
            $assert(array_key_exists($surface, $capability) && is_bool($capability[$surface]), "{$resource}.{$operation} must define {$surface} support.");
        }
    }
}
$assert(($matrix['resources']['tickets']['delete']['ios'] ?? true) === false, 'Permanent ticket deletion must not be exposed in iOS.');
foreach (['comments', 'time', 'attachments'] as $resource) {
    $assert(($matrix['resources'][$resource]['delete']['undo_seconds'] ?? null) === 10, "{$resource} deletion must expose a 10-second undo window.");
}
$assert(($matrix['resources']['tickets']['delete']['scope'] ?? '') === 'delete:write', 'Permanent ticket deletion must require delete:write.');
if ($failures) {
    fwrite(STDERR, "Capability matrix contract failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "Capability matrix contract passed.\n";
