<?php

declare(strict_types=1);

namespace App\Modules\Wallets\DTO;

final class UpdateWalletDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $currency = null,
        public readonly ?bool $isDefault = null,
    ) {
    }
}
