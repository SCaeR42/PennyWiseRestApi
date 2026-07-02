<?php

declare(strict_types=1);

namespace App\Modules\Settings\Validators;

final class SettingsValidator
{
    public function validateUpdate(array $data): array
    {
        $errors = [];

        if ($data === []) {
            $errors[] = ['field' => 'body', 'message' => 'At least one setting key is required'];

            return $errors;
        }

        foreach (array_keys($data) as $key) {
            if (!is_string($key) || $key === '' || mb_strlen($key) > 100) {
                $errors[] = ['field' => 'key', 'message' => 'Setting keys must be non-empty strings (max 100 characters)'];
                break;
            }
        }

        return $errors;
    }
}
