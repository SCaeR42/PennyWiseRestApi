<?php

declare(strict_types=1);

namespace App\Modules\Users\Validators;

use App\Core\Validation;

final class UserValidator
{
    public function validateRegister(array $data): array
    {
        $errors = [];

        if (!Validation::isEmail($data['email'] ?? null)) {
            $errors[] = ['field' => 'email', 'message' => 'A valid email is required'];
        }

        $password = $data['password'] ?? null;
        if (!is_string($password) || mb_strlen($password) < 8) {
            $errors[] = ['field' => 'password', 'message' => 'Password must be at least 8 characters'];
        }

        if (!Validation::isNonEmptyString($data['name'] ?? null, 100)) {
            $errors[] = ['field' => 'name', 'message' => 'Name is required (max 100 characters)'];
        }

        return $errors;
    }

    public function validateUpdate(array $data): array
    {
        $errors = [];

        if (array_key_exists('email', $data) && !Validation::isEmail($data['email'])) {
            $errors[] = ['field' => 'email', 'message' => 'Email must be valid'];
        }

        if (array_key_exists('name', $data) && !Validation::isNonEmptyString($data['name'], 100)) {
            $errors[] = ['field' => 'name', 'message' => 'Name must not be empty (max 100 characters)'];
        }

        return $errors;
    }
}
