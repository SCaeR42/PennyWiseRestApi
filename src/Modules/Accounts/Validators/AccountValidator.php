<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Validators;

use App\Core\Validation;

final class AccountValidator
{
    private const TYPES = ['bank', 'card', 'cash', 'e-wallet'];

    public function validateCreate(array $data): array
    {
        $errors = [];

        if (!Validation::isNonEmptyString($data['name'] ?? null, 100)) {
            $errors[] = ['field' => 'name', 'message' => 'Name is required (max 100 characters)'];
        }

        if (!Validation::isInArray($data['type'] ?? null, self::TYPES)) {
            $errors[] = ['field' => 'type', 'message' => 'Type must be one of: ' . implode(', ', self::TYPES)];
        }

        if (array_key_exists('requisites', $data) && $data['requisites'] !== null
            && !Validation::isNonEmptyString($data['requisites'], 255)) {
            $errors[] = ['field' => 'requisites', 'message' => 'Requisites must be a string (max 255 characters)'];
        }

        if (!$this->isValidCurrency($data['currency'] ?? null)) {
            $errors[] = ['field' => 'currency', 'message' => 'Currency must be a 3-letter ISO 4217 code'];
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

        if (array_key_exists('currency', $data) && !$this->isValidCurrency($data['currency'])) {
            $errors[] = ['field' => 'currency', 'message' => 'Currency must be a 3-letter ISO 4217 code'];
        }

        return $errors;
    }

    private function isValidCurrency(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[A-Z]{3}$/', $value) === 1;
    }
}
