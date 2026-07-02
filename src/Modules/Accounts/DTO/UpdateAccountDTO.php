<?php

declare(strict_types=1);

namespace App\Modules\Accounts\DTO;

final class UpdateAccountDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $requisites = null,
        public readonly ?string $currency = null,
    ) {
    }
}
