<?php

declare(strict_types=1);

namespace App\Modules\Accounts\DTO;

final class CreateAccountDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $requisites,
        public readonly string $currency,
    ) {
    }
}
