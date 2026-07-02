<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct('FORBIDDEN', $message, 403);
    }
}
