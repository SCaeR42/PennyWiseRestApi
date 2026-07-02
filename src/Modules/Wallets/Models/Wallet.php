<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Models;

final class Wallet
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly ?int $accountId,
        public readonly string $name,
        public readonly string $currency,
        public readonly float $balance,
        public readonly bool $isDefault,
        public readonly string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['account_id'] !== null ? (int) $row['account_id'] : null,
            (string) $row['name'],
            (string) $row['currency'],
            (float) $row['balance'],
            (bool) $row['is_default'],
            (string) $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'name' => $this->name,
            'currency' => $this->currency,
            'balance' => round($this->balance, 2),
            'is_default' => $this->isDefault,
            'created_at' => $this->createdAt,
        ];
    }
}
