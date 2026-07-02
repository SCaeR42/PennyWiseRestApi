<?php

declare(strict_types=1);

namespace App\Modules\Users\DTO;

final class UpdateUserDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
    ) {
    }
}
