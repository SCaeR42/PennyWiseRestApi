<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class BadRequestException extends ApiException
{
    public function __construct(string $errorCode, string $message, ?array $details = null)
    {
        parent::__construct($errorCode, $message, 400, $details);
    }

    public static function validation(array $details, string $message = 'Invalid input data'): self
    {
        return new self('VALIDATION_ERROR', $message, $details);
    }
}
