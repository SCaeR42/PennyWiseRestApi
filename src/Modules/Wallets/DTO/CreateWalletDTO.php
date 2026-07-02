<?php

declare(strict_types=1);

namespace App\Modules\Wallets\DTO;

final class CreateWalletDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $currency,
        public readonly ?int $accountId,
        public readonly bool $isDefault,
    ) {
    }
}
