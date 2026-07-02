<?php

declare(strict_types=1);

namespace App\Modules\Categories\Validators;

use App\Core\Validation;

final class CategoryValidator
{
    private const TYPES = ['income', 'expense'];

    public function validateCreate(array $data): array
    {
        $errors = [];

        if (!Validation::isNonEmptyString($data['name'] ?? null, 100)) {
            $errors[] = ['field' => 'name', 'message' => 'Name is required (max 100 characters)'];
        }

        if (!Validation::isInArray($data['type'] ?? null, self::TYPES)) {
            $errors[] = ['field' => 'type', 'message' => 'Type must be one of: ' . implode(', ', self::TYPES)];
        }

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null
            && !Validation::isNumeric($data['parent_id'])) {
            $errors[] = ['field' => 'parent_id', 'message' => 'parent_id must be numeric'];
        }

        return $errors;
    }

    public function validateUpdate(array $data): array
    {
        $errors = [];

        if (array_key_exists('name', $data) && !Validation::isNonEmptyString($data['name'], 100)) {
            $errors[] = ['field' => 'name', 'message' => 'Name must not be empty (max 100 characters)'];
        }

        if (array_key_exists('type', $data) && !Validation::isInArray($data['type'], self::TYPES)) {
            $errors[] = ['field' => 'type', 'message' => 'Type must be one of: ' . implode(', ', self::TYPES)];
        }

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null
            && !Validation::isNumeric($data['parent_id'])) {
            $errors[] = ['field' => 'parent_id', 'message' => 'parent_id must be numeric'];
        }

        return $errors;
    }
}
