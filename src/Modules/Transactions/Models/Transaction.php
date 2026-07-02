<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Models;

final class Transaction
{
    /**
     * @param list<int> $tagIds
     */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $walletId,
        public readonly int $categoryId,
        public readonly string $type,
        public readonly float $amount,
        public readonly ?string $description,
        public readonly string $date,
        public readonly string $createdAt,
        public readonly array $tagIds = [],
    ) {
    }

    public static function fromRow(array $row, array $tagIds = []): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['wallet_id'],
            (int) $row['category_id'],
            (string) $row['type'],
            (float) $row['amount'],
            $row['description'] !== null ? (string) $row['description'] : null,
            (string) $row['date'],
            (string) $row['created_at'],
            $tagIds,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->walletId,
            'category_id' => $this->categoryId,
            'type' => $this->type,
            'amount' => round($this->amount, 2),
            'description' => $this->description,
            'date' => $this->date,
            'created_at' => $this->createdAt,
            'tags' => $this->tagIds,
        ];
    }
}
