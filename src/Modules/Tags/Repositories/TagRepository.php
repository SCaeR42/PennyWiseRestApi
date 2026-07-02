<?php

declare(strict_types=1);

namespace App\Modules\Tags\Repositories;

use App\Modules\Tags\Models\Tag;

final class TagRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM tags WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(Tag::fromRow(...), $stmt->fetchAll());

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM tags WHERE user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        return ['items' => $items, 'total' => $total];
    }

    public function findForUser(int $id, int $userId): ?Tag
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tags WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : Tag::fromRow($row);
    }

    /**
     * @param list<int> $ids
     * @return list<Tag>
     */
    public function findManyForUser(array $ids, int $userId): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM tags WHERE user_id = ? AND id IN ({$placeholders})"
        );
        $stmt->execute([$userId, ...$ids]);

        return array_map(Tag::fromRow(...), $stmt->fetchAll());
    }

    public function create(int $userId, string $name, string $color): Tag
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tags (user_id, name, color) VALUES (:user_id, :name, :color)'
        );
        $stmt->execute(['user_id' => $userId, 'name' => $name, 'color' => $color]);

        return $this->findForUser((int) $this->pdo->lastInsertId(), $userId);
    }

    public function update(int $id, int $userId, array $fields): ?Tag
    {
        if ($fields !== []) {
            $assignments = implode(', ', array_map(static fn ($field) => "{$field} = :{$field}", array_keys($fields)));
            $stmt = $this->pdo->prepare(
                "UPDATE tags SET {$assignments} WHERE id = :id AND user_id = :user_id"
            );
            $stmt->execute([...$fields, 'id' => $id, 'user_id' => $userId]);
        }

        return $this->findForUser($id, $userId);
    }

    public function delete(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tags WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
