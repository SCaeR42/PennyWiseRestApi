<?php

declare(strict_types=1);

namespace App\Modules\Users\DTO;

final class RegisterUserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $name,
    ) {
    }
}
