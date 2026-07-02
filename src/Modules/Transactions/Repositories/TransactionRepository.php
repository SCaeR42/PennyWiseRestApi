<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Repositories;

use App\Modules\Transactions\Models\Transaction;

final class TransactionRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage, array $filters = []): array
    {
        $conditions = ['user_id = :user_id'];
        $params = ['user_id' => $userId];

        if (isset($filters['wallet_id'])) {
            $conditions[] = 'wallet_id = :wallet_id';
            $params['wallet_id'] = $filters['wallet_id'];
        }
        if (isset($filters['category_id'])) {
            $conditions[] = 'category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        if (isset($filters['type'])) {
            $conditions[] = 'type = :type';
            $params['type'] = $filters['type'];
        }
        if (isset($filters['date_from'])) {
            $conditions[] = 'date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (isset($filters['date_to'])) {
            $conditions[] = 'date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM transactions WHERE {$where} ORDER BY date DESC, id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $items = array_map(
            fn (array $row) => Transaction::fromRow($row, $this->getTagIds((int) $row['id'])),
            $rows,
        );

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM transactions WHERE {$where}");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public function findForUser(int $id, int $userId): ?Transaction
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Transaction::fromRow($row, $this->getTagIds($id));
    }

    /**
     * @return list<int>
     */
    public function getTagIds(int $transactionId): array
    {
        $stmt = $this->pdo->prepare('SELECT tag_id FROM transaction_tag WHERE transaction_id = :id');
        $stmt->execute(['id' => $transactionId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function create(
        int $userId,
        int $walletId,
        int $categoryId,
        string $type,
        float $amount,
        ?string $description,
        string $date,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, category_id, type, amount, description, date)
             VALUES (:user_id, :wallet_id, :category_id, :type, :amount, :description, :date)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'wallet_id' => $walletId,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'date' => $date,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateFields(int $id, int $userId, array $fields): void
    {
        if ($fields === []) {
            return;
        }

        $assignments = implode(', ', array_map(static fn ($field) => "{$field} = :{$field}", array_keys($fields)));
        $stmt = $this->pdo->prepare("UPDATE transactions SET {$assignments} WHERE id = :id AND user_id = :user_id");
        $stmt->execute([...$fields, 'id' => $id, 'user_id' => $userId]);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM transactions WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    /**
     * @param list<int> $tagIds
     */
    public function syncTags(int $transactionId, array $tagIds): void
    {
        $this->pdo->prepare('DELETE FROM transaction_tag WHERE transaction_id = :id')
            ->execute(['id' => $transactionId]);

        if ($tagIds === []) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO transaction_tag (transaction_id, tag_id) VALUES (:transaction_id, :tag_id)'
        );
        foreach ($tagIds as $tagId) {
            $stmt->execute(['transaction_id' => $transactionId, 'tag_id' => $tagId]);
        }
    }
}
