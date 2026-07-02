<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class ProcessingException extends ApiException
{
    public function __construct(string $message, string $errorCode = 'PROCESSING_ERROR')
    {
        parent::__construct($errorCode, $message, 422);
    }
}
