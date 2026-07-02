<?php

declare(strict_types=1);

namespace App\Modules\EmailVerification\DTO;

final class VerifyEmailsRequestDTO
{
    /**
     * @param list<string> $emails
     */
    public function __construct(public readonly array $emails)
    {
    }
}
