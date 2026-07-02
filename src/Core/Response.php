<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private function __construct(
        public readonly int $status,
        private readonly array $payload,
    ) {
    }

    public static function success(mixed $data, ?array $meta = null, int $status = 200): self
    {
        $payload = ['success' => true, 'data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return new self($status, $payload);
    }

    public static function error(string $code, string $message, int $status, ?array $details = null): self
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== null) {
            $error['details'] = $details;
        }

        return new self($status, ['success' => false, 'error' => $error]);
    }

    public function send(): void
    {
        http_response_code($this->status);

        if ($this->status === 204) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
