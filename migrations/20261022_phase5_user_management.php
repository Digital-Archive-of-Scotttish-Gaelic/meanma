<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

chdir(dirname(__DIR__));
require_once 'includes/include.php';

$connection = (new models\database())->getDatabaseHandle();
$schema = (string) $connection->query('SELECT DATABASE()')->fetchColumn();
$columnExists = static function (string $column) use ($connection, $schema): bool {
    $statement = $connection->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=:schema AND TABLE_NAME=\'user\' AND COLUMN_NAME=:column'
    );
    $statement->execute([':schema' => $schema, ':column' => $column]);
    return (int) $statement->fetchColumn() > 0;
};

if (!$columnExists('must_change_password')) {
    $connection->exec('ALTER TABLE user ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0');
}
if (!$columnExists('auth_version')) {
    $connection->exec('ALTER TABLE user ADD COLUMN auth_version BIGINT UNSIGNED NOT NULL DEFAULT 1');
}
$connection->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS security_audit (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      actor_email VARCHAR(255) NOT NULL,
      target_email VARCHAR(255) NULL,
      action VARCHAR(64) NOT NULL,
      outcome VARCHAR(32) NOT NULL,
      details_json TEXT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX security_audit_created_at_idx (created_at),
      INDEX security_audit_actor_idx (actor_email),
      INDEX security_audit_target_idx (target_email)
    ) ENGINE=InnoDB
SQL);

echo "Phase 5 user-management schema is ready.\n";
