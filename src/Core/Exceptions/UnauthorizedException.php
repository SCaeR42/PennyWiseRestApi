<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class UnauthorizedException extends ApiException
{
    public function __construct(string $message = 'Unauthorized', string $errorCode = 'UNAUTHORIZED')
    {
        parent::__construct($errorCode, $message, 401);
    }
}
