<?php

declare(strict_types=1);

namespace App\Modules\Categories\DTO;

final class UpdateCategoryDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?int $parentId = null,
        public readonly bool $parentIdProvided = false,
    ) {
    }
}
