<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Not found')
    {
        parent::__construct('NOT_FOUND', $message, 404);
    }
}
