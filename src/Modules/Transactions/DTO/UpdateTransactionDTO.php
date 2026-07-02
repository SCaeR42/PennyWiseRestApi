<?php

declare(strict_types=1);

namespace App\Modules\Transactions\DTO;

final class UpdateTransactionDTO
{
    /**
     * @param list<int>|null $tagIds
     */
    public function __construct(
        public readonly ?int $walletId = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $type = null,
        public readonly ?float $amount = null,
        public readonly ?string $description = null,
        public readonly bool $descriptionProvided = false,
        public readonly ?string $date = null,
        public readonly ?array $tagIds = null,
        public readonly bool $tagIdsProvided = false,
    ) {
    }
}
