<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Каждый тест выполняется в своей БД-транзакции, откатываемой в tearDown —
 * тестовые пользователи/кошельки/категории не засоряют реальную БД
 * (в частности, не трогают уже существующие demo-аккаунты вроде Alice/Bob).
 */
abstract class IsolatedTransactionTestCase extends TestCase
{
    protected \PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Database::connection();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    protected function createUser(string $email): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password, name) VALUES (:email, :password, :name)'
        );
        $stmt->execute([
            'email' => $email,
            'password' => password_hash('integration-test-password', PASSWORD_BCRYPT),
            'name' => 'Integration Test User',
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
