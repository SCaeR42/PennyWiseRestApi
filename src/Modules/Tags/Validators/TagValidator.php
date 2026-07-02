<?php

declare(strict_types=1);

namespace App\Modules\Tags\Validators;

use App\Core\Validation;

final class TagValidator
{
    public function validateCreate(array $data): array
    {
        $errors = [];

        if (!Validation::isNonEmptyString($data['name'] ?? null, 50)) {
            $errors[] = ['field' => 'name', 'message' => 'Name is required (max 50 characters)'];
        }

        if (array_key_exists('color', $data) && !Validation::isHexColor($data['color'])) {
            $errors[] = ['field' => 'color', 'message' => 'Color must be a HEX value, e.g. #A1B2C3'];
        }

        return $errors;
    }

    public function validateUpdate(array $data): array
    {
        $errors = [];

        if (array_key_exists('name', $data) && !Validation::isNonEmptyString($data['name'], 50)) {
            $errors[] = ['field' => 'name', 'message' => 'Name must not be empty (max 50 characters)'];
        }

        if (array_key_exists('color', $data) && !Validation::isHexColor($data['color'])) {
            $errors[] = ['field' => 'color', 'message' => 'Color must be a HEX value, e.g. #A1B2C3'];
        }

        return $errors;
    }
}
