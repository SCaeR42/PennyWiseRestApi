<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Validators;

use App\Core\Validation;

final class TransactionValidator
{
    private const TYPES = ['income', 'expense'];

    public function validateCreate(array $data): array
    {
        $errors = [];

        if (!Validation::isNumeric($data['wallet_id'] ?? null)) {
            $errors[] = ['field' => 'wallet_id', 'message' => 'wallet_id is required and must be numeric'];
        }

        if (!Validation::isNumeric($data['category_id'] ?? null)) {
            $errors[] = ['field' => 'category_id', 'message' => 'category_id is required and must be numeric'];
        }

        if (!Validation::isInArray($data['type'] ?? null, self::TYPES)) {
            $errors[] = ['field' => 'type', 'message' => 'Type must be one of: ' . implode(', ', self::TYPES)];
        }

        if (!Validation::isPositiveNumber($data['amount'] ?? null)) {
            $errors[] = ['field' => 'amount', 'message' => 'Amount is required and must be a positive number'];
        }

        if (!Validation::isDate($data['date'] ?? null)) {
            $errors[] = ['field' => 'date', 'message' => 'Date is required in YYYY-MM-DD format'];
        }

        if (array_key_exists('tags', $data) && !Validation::isArrayOfInt($data['tags'])) {
            $errors[] = ['field' => 'tags', 'message' => 'Tags must be an array of tag IDs'];
        }

        return $errors;
    }

    public function validateUpdate(array $data): array
    {
        $errors = [];

        if (array_key_exists('wallet_id', $data) && !Validation::isNumeric($data['wallet_id'])) {
            $errors[] = ['field' => 'wallet_id', 'message' => 'wallet_id must be numeric'];
        }

        if (array_key_exists('category_id', $data) && !Validation::isNumeric($data['category_id'])) {
            $errors[] = ['field' => 'category_id', 'message' => 'category_id must be numeric'];
        }

        if (array_key_exists('type', $data) && !Validation::isInArray($data['type'], self::TYPES)) {
            $errors[] = ['field' => 'type', 'message' => 'Type must be one of: ' . implode(', ', self::TYPES)];
        }

        if (array_key_exists('amount', $data) && !Validation::isPositiveNumber($data['amount'])) {
            $errors[] = ['field' => 'amount', 'message' => 'Amount must be a positive number'];
        }

        if (array_key_exists('date', $data) && !Validation::isDate($data['date'])) {
            $errors[] = ['field' => 'date', 'message' => 'Date must be in YYYY-MM-DD format'];
        }

        if (array_key_exists('tags', $data) && !Validation::isArrayOfInt($data['tags'])) {
            $errors[] = ['field' => 'tags', 'message' => 'Tags must be an array of tag IDs'];
        }

        return $errors;
    }
}
