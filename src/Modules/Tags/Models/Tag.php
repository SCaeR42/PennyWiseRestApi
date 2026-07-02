<?php

declare(strict_types=1);

namespace App\Modules\Tags\Models;

final class Tag
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $name,
        public readonly string $color,
        public readonly string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['name'],
            (string) $row['color'],
            (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'created_at' => $this->createdAt,
        ];
    }
}
