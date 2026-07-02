<?php

declare(strict_types=1);

namespace App\Modules\Tags\DTO;

final class UpdateTagDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $color = null,
    ) {
    }
}
