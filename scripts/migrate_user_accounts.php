<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = getDB();
$db->exec(
    "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB"
);

$userIdColumn = $db->query("SHOW COLUMNS FROM reports LIKE 'user_id'")->fetch();
if (!$userIdColumn) {
    $db->exec(
        'ALTER TABLE reports
            ADD COLUMN user_id INT UNSIGNED NULL AFTER id,
            ADD INDEX idx_reports_user_created (user_id, created_at),
            ADD CONSTRAINT fk_reports_user FOREIGN KEY (user_id)
                REFERENCES users(id) ON DELETE SET NULL'
    );
}

fwrite(STDOUT, "Migración de cuentas de usuario completada. Los reportes existentes se conservaron.\n");
