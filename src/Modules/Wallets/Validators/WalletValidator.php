<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Validators;

use App\Core\Validation;

final class WalletValidator
{
    public function validateCreate(array $data): array
    {
        $errors = [];

        if (!Validation::isNonEmptyString($data['name'] ?? null, 100)) {
            $errors[] = ['field' => 'name', 'message' => 'Name is required (max 100 characters)'];
        }

        if (!$this->isValidCurrency($data['currency'] ?? null)) {
            $errors[] = ['field' => 'currency', 'message' => 'Currency must be a 3-letter ISO 4217 code'];
        }

        if (array_key_exists('account_id', $data) && $data['account_id'] !== null
            && !Validation::isNumeric($data['account_id'])) {
            $errors[] = ['field' => 'account_id', 'message' => 'account_id must be numeric'];
        }

        if (array_key_exists('is_default', $data) && !Validation::isBool($data['is_default'])) {
            $errors[] = ['field' => 'is_default', 'message' => 'is_default must be a boolean'];
        }

        return $errors;
    }

    public function validateUpdate(array $data): array
    {
        $errors = [];

        if (array_key_exists('name', $data) && !Validation::isNonEmptyString($data['name'], 100)) {
            $errors[] = ['field' => 'name', 'message' => 'Name must not be empty (max 100 characters)'];
        }

        if (array_key_exists('currency', $data) && !$this->isValidCurrency($data['currency'])) {
            $errors[] = ['field' => 'currency', 'message' => 'Currency must be a 3-letter ISO 4217 code'];
        }

        if (array_key_exists('is_default', $data) && !Validation::isBool($data['is_default'])) {
            $errors[] = ['field' => 'is_default', 'message' => 'is_default must be a boolean'];
        }

        return $errors;
    }

    private function isValidCurrency(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[A-Z]{3}$/', $value) === 1;
    }
}
