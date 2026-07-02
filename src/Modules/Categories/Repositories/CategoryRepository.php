<?php

declare(strict_types=1);

namespace App\Modules\Categories\Repositories;

use App\Modules\Categories\Models\Category;

final class CategoryRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(Category::fromRow(...), $stmt->fetchAll());

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM categories WHERE user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public function findForUser(int $id, int $userId): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Category::fromRow($row);
    }

    public function create(int $userId, ?int $parentId, string $name, string $type): Category
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (user_id, parent_id, name, type) VALUES (:user_id, :parent_id, :name, :type)'
        );
        $stmt->execute(['user_id' => $userId, 'parent_id' => $parentId, 'name' => $name, 'type' => $type]);

        return $this->findForUser((int) $this->pdo->lastInsertId(), $userId);
    }

    public function update(int $id, int $userId, array $fields): ?Category
    {
        if ($fields !== []) {
            $assignments = implode(', ', array_map(static fn ($field) => "{$field} = :{$field}", array_keys($fields)));
            $stmt = $this->pdo->prepare(
                "UPDATE categories SET {$assignments} WHERE id = :id AND user_id = :user_id"
            );
            $stmt->execute([...$fields, 'id' => $id, 'user_id' => $userId]);
        }

        return $this->findForUser($id, $userId);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
