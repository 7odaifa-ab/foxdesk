<?php

return static function (PDO $db): void {
    $columns = [
        ['table' => 'users', 'column' => 'language', 'definition' => "VARCHAR(35) DEFAULT 'en'"],
        ['table' => 'email_templates', 'column' => 'language', 'definition' => "VARCHAR(35) DEFAULT 'en'"],
        ['table' => 'report_templates', 'column' => 'report_language', 'definition' => "VARCHAR(35) DEFAULT 'en'"],
    ];

    $lookup = $db->prepare(
        'SELECT CHARACTER_MAXIMUM_LENGTH
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
         LIMIT 1'
    );

    foreach ($columns as $column) {
        $lookup->execute([$column['table'], $column['column']]);
        $length = $lookup->fetchColumn();
        if ($length === false || (int) $length >= 35) {
            continue;
        }
        $db->exec(sprintf(
            'ALTER TABLE `%s` MODIFY COLUMN `%s` %s',
            $column['table'],
            $column['column'],
            $column['definition']
        ));
    }
};
