<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

abstract class ApiException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $httpStatus,
        private readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function details(): ?array
    {
        return $this->details;
    }
}
