<?php

return static function (PDO $db): void {
    $tableExists = static function (string $table) use ($db): bool {
        $stmt = $db->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    };
    $columnExists = static function (string $table, string $column) use ($db): bool {
        $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetchColumn();
    };
    $indexExists = static function (string $table, string $index) use ($db): bool {
        $stmt = $db->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = ?");
        $stmt->execute([$index]);
        return (bool) $stmt->fetchColumn();
    };

    if (!$tableExists('ticket_time_entries')) {
        return;
    }
    if (!$columnExists('ticket_time_entries', 'worked_on')) {
        $db->exec('ALTER TABLE ticket_time_entries ADD COLUMN worked_on DATE NULL AFTER comment_id');
    }
    if (!$columnExists('ticket_time_entries', 'time_precision')) {
        $db->exec("ALTER TABLE ticket_time_entries ADD COLUMN time_precision VARCHAR(24) NOT NULL DEFAULT 'exact' AFTER worked_on");
    }
    if (!$indexExists('ticket_time_entries', 'idx_worked_on')) {
        $db->exec('ALTER TABLE ticket_time_entries ADD INDEX idx_worked_on (worked_on)');
    }
    $db->exec(
        "UPDATE ticket_time_entries
         SET worked_on = DATE(started_at)
         WHERE worked_on IS NULL AND started_at IS NOT NULL"
    );
};
