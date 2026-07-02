#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

$pdo = Database::connection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_migrations_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(\PDO::FETCH_COLUMN);

$migrationsDir = __DIR__ . '/../database/migrations';
$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files, SORT_STRING);

$ranCount = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }

    $sql = trim((string) file_get_contents($file));
    if ($sql === '') {
        continue;
    }

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $stmt->execute(['migration' => $name]);
        echo "Applied: {$name}\n";
        $ranCount++;
    } catch (\Throwable $e) {
        fwrite(STDERR, "Failed: {$name} - {$e->getMessage()}\n");
        exit(1);
    }
}

echo $ranCount === 0 ? "Nothing to migrate.\n" : "Applied {$ranCount} migration(s).\n";
