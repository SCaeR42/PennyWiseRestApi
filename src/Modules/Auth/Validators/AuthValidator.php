<?php

declare(strict_types=1);

namespace App\Modules\Auth\Validators;

use App\Core\Validation;

final class AuthValidator
{
    public function validateLogin(array $data): array
    {
        $errors = [];

        if (!Validation::isEmail($data['email'] ?? null)) {
            $errors[] = ['field' => 'email', 'message' => 'A valid email is required'];
        }

        if (!Validation::isNonEmptyString($data['password'] ?? null, 255)) {
            $errors[] = ['field' => 'password', 'message' => 'Password is required'];
        }

        return $errors;
    }

    public function validateRefresh(array $data): array
    {
        $errors = [];

        if (!Validation::isNonEmptyString($data['refresh_token'] ?? null, 2048)) {
            $errors[] = ['field' => 'refresh_token', 'message' => 'refresh_token is required'];
        }

        return $errors;
    }
}
