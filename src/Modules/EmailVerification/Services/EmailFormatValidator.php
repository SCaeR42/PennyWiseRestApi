<?php

declare(strict_types=1);

namespace App\Modules\EmailVerification\Services;

final class EmailFormatValidator
{
    private const PATTERN = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)+$/';

    public function isValid(string $email): bool
    {
        return preg_match(self::PATTERN, $email) === 1;
    }

    public function extractDomain(string $email): ?string
    {
        $at = strrpos($email, '@');

        return $at === false ? null : substr($email, $at + 1);
    }
}
