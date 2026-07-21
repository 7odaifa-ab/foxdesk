<?php
/** Move request-time schema mutations into the self-hosted upgrade path. */
return static function (PDO $db): void {
    $columnExists = static function (string $table, string $column) use ($db): bool {
        $stmt = $db->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');
        $stmt->execute([$column]);
        return (bool) $stmt->fetchColumn();
    };
    $tableExists = static function (string $table) use ($db): bool {
        $stmt = $db->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    };
    $indexExists = static function (string $table, string $index) use ($db): bool {
        $stmt = $db->prepare('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?');
        $stmt->execute([$index]);
        return (bool) $stmt->fetchColumn();
    };
    $addColumn = static function (string $table, string $column, string $definition) use ($db, $columnExists): void {
        if (!$columnExists($table, $column)) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    };
    $addIndex = static function (string $table, string $index, string $columns) use ($db, $indexExists): void {
        if (!$indexExists($table, $index)) {
            $db->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
        }
    };

    foreach ([
        'remember_token' => 'VARCHAR(64) NULL',
        'notification_preferences' => 'TEXT NULL',
        'is_ai_agent' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'ai_model' => 'VARCHAR(100) NULL',
        'totp_secret' => 'VARCHAR(64) NULL',
        'totp_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'totp_backup_codes' => 'TEXT NULL',
        'last_notifications_seen_at' => 'DATETIME NULL',
    ] as $column => $definition) {
        $addColumn('users', $column, $definition);
    }

    foreach ([
        'due_days' => 'INT NOT NULL DEFAULT 7',
        'paused_at' => 'DATETIME NULL',
        'resume_date' => 'DATE NULL',
        'tags' => 'TEXT NULL',
    ] as $column => $definition) {
        $addColumn('recurring_tasks', $column, $definition);
    }

    foreach ([
        'tags' => 'TEXT NULL',
        'agent_ids' => 'TEXT NULL',
        'schedule_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'schedule_interval' => "VARCHAR(20) NOT NULL DEFAULT 'monthly'",
        'schedule_day' => 'INT NOT NULL DEFAULT 1',
        'schedule_recipients' => 'TEXT NULL',
        'schedule_last_sent' => 'DATETIME NULL',
        'schedule_next_due' => 'DATE NULL',
        'expires_at' => 'DATETIME NULL',
        'custom_billable_rate' => 'DECIMAL(10,2) NULL',
    ] as $column => $definition) {
        $addColumn('report_templates', $column, $definition);
    }

    foreach ([
        'sender_email' => 'VARCHAR(255) NULL',
        'subject' => 'VARCHAR(255) NULL',
        'ticket_id' => 'INT NULL',
    ] as $column => $definition) {
        $addColumn('email_ingest_logs', $column, $definition);
    }
    $addIndex('email_ingest_logs', 'idx_sender_email', '`sender_email`');
    $addIndex('email_ingest_logs', 'idx_ticket_id', '`ticket_id`');

    $addColumn('notifications', 'actor_id', 'INT NULL');
    $addColumn('notifications', 'data', 'JSON NULL');
    $addColumn('notifications', 'is_resolved', 'TINYINT(1) NOT NULL DEFAULT 0');

    if (!$tableExists('recurring_task_runs')) {
        $db->exec("CREATE TABLE recurring_task_runs (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            recurring_task_id INT NOT NULL,
            ticket_id INT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'success',
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recurring_ticket (ticket_id),
            INDEX idx_recurring_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!$tableExists('ticket_history')) {
        $db->exec("CREATE TABLE ticket_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            old_value TEXT NULL,
            new_value TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket (ticket_id), INDEX idx_user (user_id), INDEX idx_created (created_at),
            FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!$tableExists('agent_client_billable_rates')) {
        $db->exec("CREATE TABLE agent_client_billable_rates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            user_id INT NOT NULL,
            billable_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_agent_client_rate (organization_id, user_id),
            INDEX idx_organization (organization_id), INDEX idx_user (user_id), INDEX idx_active (is_active),
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    if (!$tableExists('push_subscriptions')) {
        $db->exec("CREATE TABLE push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh VARCHAR(255) NOT NULL DEFAULT '',
            auth_key VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_push_user (user_id), INDEX idx_push_endpoint (endpoint(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
};
