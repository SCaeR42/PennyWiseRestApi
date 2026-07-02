<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Models;

final class Account
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $requisites,
        public readonly string $currency,
        public readonly string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['name'],
            (string) $row['type'],
            $row['requisites'] !== null ? (string) $row['requisites'] : null,
            (string) $row['currency'],
            (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'requisites' => $this->requisites,
            'currency' => $this->currency,
            'created_at' => $this->createdAt,
        ];
    }
}
