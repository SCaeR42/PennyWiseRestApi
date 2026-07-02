<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Repositories;

use App\Modules\Wallets\Models\Wallet;

final class WalletRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM wallets WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(Wallet::fromRow(...), $stmt->fetchAll());

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM wallets WHERE user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public function findForUser(int $id, int $userId): ?Wallet
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Wallet::fromRow($row);
    }

    public function create(int $userId, ?int $accountId, string $name, string $currency, bool $isDefault): Wallet
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, account_id, name, currency, is_default)
             VALUES (:user_id, :account_id, :name, :currency, :is_default)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'account_id' => $accountId,
            'name' => $name,
            'currency' => $currency,
            'is_default' => $isDefault ? 1 : 0,
        ]);

        return $this->findForUser((int) $this->pdo->lastInsertId(), $userId);
    }

    public function update(int $id, int $userId, array $fields): ?Wallet
    {
        if ($fields !== []) {
            $assignments = implode(', ', array_map(static fn ($field) => "{$field} = :{$field}", array_keys($fields)));
            $stmt = $this->pdo->prepare(
                "UPDATE wallets SET {$assignments} WHERE id = :id AND user_id = :user_id"
            );
            $stmt->execute([...$fields, 'id' => $id, 'user_id' => $userId]);
        }

        return $this->findForUser($id, $userId);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM wallets WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    /**
     * Пересчитывает balance кошелька как сумму income/expense транзакций.
     * Вызывается сервисным слоем Transactions в той же БД-транзакции, что и
     * запись изменений в transactions (см. SDD 4.2).
     */
    public function recalculateBalance(int $walletId): void
    {
        // Native (non-emulated) prepared statements can't reuse one named placeholder
        // twice in a query, hence two distinct params bound to the same value.
        $stmt = $this->pdo->prepare(
            "UPDATE wallets
             SET balance = COALESCE((
                 SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END)
                 FROM transactions
                 WHERE wallet_id = :wallet_id_sum
             ), 0)
             WHERE id = :wallet_id_where"
        );
        $stmt->execute(['wallet_id_sum' => $walletId, 'wallet_id_where' => $walletId]);
    }
}
