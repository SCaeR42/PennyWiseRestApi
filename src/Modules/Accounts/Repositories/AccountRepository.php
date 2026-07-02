<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Repositories;

use App\Modules\Accounts\Models\Account;

final class AccountRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(Account::fromRow(...), $stmt->fetchAll());

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM accounts WHERE user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public function findForUser(int $id, int $userId): ?Account
    {
        $stmt = $this->pdo->prepare('SELECT * FROM accounts WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Account::fromRow($row);
    }

    public function create(int $userId, string $name, string $type, ?string $requisites, string $currency): Account
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accounts (user_id, name, type, requisites, currency)
             VALUES (:user_id, :name, :type, :requisites, :currency)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'requisites' => $requisites,
            'currency' => $currency,
        ]);

        return $this->findForUser((int) $this->pdo->lastInsertId(), $userId);
    }

    public function update(int $id, int $userId, array $fields): ?Account
    {
        if ($fields !== []) {
            $assignments = implode(', ', array_map(static fn ($field) => "{$field} = :{$field}", array_keys($fields)));
            $stmt = $this->pdo->prepare(
                "UPDATE accounts SET {$assignments} WHERE id = :id AND user_id = :user_id"
            );
            $stmt->execute([...$fields, 'id' => $id, 'user_id' => $userId]);
        }

        return $this->findForUser($id, $userId);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM accounts WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
