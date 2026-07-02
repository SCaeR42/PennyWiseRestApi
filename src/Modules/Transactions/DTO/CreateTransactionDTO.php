<?php

declare(strict_types=1);

namespace App\Modules\Transactions\DTO;

final class CreateTransactionDTO
{
    /**
     * @param list<int> $tagIds
     */
    public function __construct(
        public readonly int $walletId,
        public readonly int $categoryId,
        public readonly string $type,
        public readonly float $amount,
        public readonly ?string $description,
        public readonly string $date,
        public readonly array $tagIds = [],
    ) {
    }
}
