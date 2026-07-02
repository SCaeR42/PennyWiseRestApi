<?php

declare(strict_types=1);

namespace App\Modules\Categories\Models;

final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly ?int $parentId,
        public readonly string $name,
        public readonly string $type,
        public readonly string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            (string) $row['name'],
            (string) $row['type'],
            (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'type' => $this->type,
            'created_at' => $this->createdAt,
        ];
    }
}
