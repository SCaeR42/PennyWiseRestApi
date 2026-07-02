<?php

declare(strict_types=1);

namespace App\Modules\Categories\DTO;

final class CreateCategoryDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?int $parentId,
    ) {
    }
}
