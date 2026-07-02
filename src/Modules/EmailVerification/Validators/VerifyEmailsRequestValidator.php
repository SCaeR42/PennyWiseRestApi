<?php

declare(strict_types=1);

namespace App\Modules\EmailVerification\Validators;

final class VerifyEmailsRequestValidator
{
    public const MAX_BATCH_SIZE = 100;

    public function validate(array $data): array
    {
        $errors = [];

        if (!array_key_exists('emails', $data) || !is_array($data['emails'])) {
            $errors[] = ['field' => 'emails', 'message' => 'emails must be an array of strings'];

            return $errors;
        }

        if ($data['emails'] === []) {
            $errors[] = ['field' => 'emails', 'message' => 'emails must not be empty'];

            return $errors;
        }

        foreach ($data['emails'] as $item) {
            if (!is_string($item)) {
                $errors[] = ['field' => 'emails', 'message' => 'Each item in emails must be a string'];
                break;
            }
        }

        return $errors;
    }
}
