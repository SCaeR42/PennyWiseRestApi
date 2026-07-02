<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\ApiException;

final class ExceptionHandler
{
    public function handle(\Throwable $exception): Response
    {
        if ($exception instanceof ApiException) {
            return Response::error(
                $exception->errorCode(),
                $exception->getMessage(),
                $exception->httpStatus(),
                $exception->details(),
            );
        }

        error_log((string) $exception);

        $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

        return Response::error(
            'INTERNAL_ERROR',
            $debug ? $exception->getMessage() : 'Internal server error',
            500,
        );
    }
}
