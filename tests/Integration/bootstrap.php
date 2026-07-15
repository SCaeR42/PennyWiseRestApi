<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

if (file_exists(__DIR__ . '/../../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
}

// Позволяет переопределить подключение к БД для локального (не-Docker) запуска
// независимо от того, что уже в .env / его отсутствия, например:
//   DB_HOST=127.0.0.1 vendor/bin/phpunit -c phpunit.integration.xml
// Внутри контейнера DB_HOST=db уже приходит как реальная env-переменная
// (docker-compose "environment:") и .env там вообще отсутствует (.dockerignore).
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $key) {
    $value = getenv($key);
    if ($value !== false) {
        $_ENV[$key] = $value;
    }
}
