<?php

declare(strict_types=1);

namespace App\Modules\Tags\DTO;

final class CreateTagDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $color,
    ) {
    }
}
